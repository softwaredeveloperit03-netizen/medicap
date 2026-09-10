import { AfterViewInit, Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new2',
  templateUrl: './new2.component.html',
  styleUrls: ['./new2.component.css']
})
export class New2Component implements AfterViewInit {

  constructor(private service: DataAccessService, private route: ActivatedRoute, private router: Router) {}

  plant_id = localStorage.getItem('plant_id');
  details: any = {};
  testN: any = '';
  testCode: any = '';
  testType: any = '';
  test_master_id: any = '';
  specTestId: any = '';
  selectedMethod: any = {};
  isView = false;
  moaMode: 'test' | 'specification' = 'specification';

  generalInfoList: any[] = [];
  purposeList: any[] = [];
  scopeList: any[] = [];
  safetyList: any[] = [];
  safetyHtml = '';
  procedureHtml = '';
  chromatographicHtml = '';
  definitionList: any[] = [];
  testingInstructionList: any[] = [];
  procedureList: any[] = [];
  associateDocuments: any[] = [];
  referenceDocuments: any[] = [];
  equipmentList: any[] = [];
  balanceList: any[] = [];
  chemicalList: any[] = [];
  glasswareList: any[] = [];
  volumetricList: any[] = [];
  mobilePhaseList: any[] = [];

  ngAfterViewInit() {
    const mode = String(this.route.snapshot.queryParamMap.get('moaMode') || '').toLowerCase();
    this.moaMode = mode === 'test' ? 'test' : 'specification';
    this.route.paramMap.subscribe((params) => {
      this.getMaterialDetails(params.get('id'));
    });
  }

  private applyMethodPayload(methods: any): void {
    this.selectedMethod = methods && typeof methods === 'object' ? methods : {};
    this.generalInfoList = this.toValueList(this.selectedMethod['Genral_Instruction']);
    this.purposeList = this.toValueList(this.selectedMethod['purpose']);
    this.scopeList = this.toValueList(this.selectedMethod['Scope']);
    this.safetyHtml = this.toRichHtml(this.selectedMethod['Safety']);
    this.safetyList = this.safetyHtml ? [] : this.toValueList(this.selectedMethod['Safety']);
    this.definitionList = this.toValueList(this.selectedMethod['defination']);
    this.testingInstructionList = this.toArray(this.selectedMethod['testinginstruction']);
    this.procedureHtml = this.toRichHtml(this.selectedMethod['Procedure'] || this.selectedMethod['procedure']);
    this.procedureList = this.procedureHtml ? [] : this.toValueList(this.selectedMethod['Procedure'] || this.selectedMethod['procedure']);
    this.chromatographicHtml = this.toRichHtml(this.selectedMethod['chromatographic_conditions']);
    this.associateDocuments = this.toArray(this.selectedMethod['Associative_Document']);
    this.referenceDocuments = this.toArray(this.selectedMethod['Refrenced_Document']);
    this.equipmentList = this.toArray(this.selectedMethod['equipment_instruments']);
    this.balanceList = this.toArray(this.selectedMethod['balance']);
    this.chemicalList = this.toArray(this.selectedMethod['chemical_reagents']);
    this.glasswareList = this.toArray(this.selectedMethod['glasswares']);
    this.volumetricList = this.toArray(this.selectedMethod['volumetric_solutions']);
    this.mobilePhaseList = this.toArray(this.selectedMethod['phases']);
  }

  getMaterialDetails(id) {
    this.specTestId = id;
    const modeQs = this.moaMode === 'test' ? '&moa_mode=test' : '';
    this.service.get('qc/method.php?type=get_test_data&id=' + id + modeQs).subscribe((response: any) => {
      const payload = Array.isArray(response) ? (response[0] || {}) : (response || {});
      if (payload?.status === 'failed') {
        alertify.error(payload?.message || 'Unable to load this method.');
        this.isView = false;
        return;
      }

      this.details = payload;
      if (String(this.details['moa_mode'] || '') === 'test') {
        this.moaMode = 'test';
      }

      this.testN = this.details['test'] || '';
      this.testCode = this.details['test_method_no'] || this.details['method_id'] || '';
      this.testType = this.details['test_type'] || '';
      this.test_master_id = this.details['test_master_id'] || this.details['id'] || id;

      const methods = this.details['methods'];
      if (this.moaMode === 'test') {
        this.applyMethodPayload(
          methods && typeof methods === 'object' && !Array.isArray(methods) ? methods : {}
        );
        this.isView = true;
        return;
      }

      if (methods && typeof methods === 'object' && !Array.isArray(methods)) {
        this.applyMethodPayload(methods);
        this.isView = true;
        if (this.hasAnyMethodContent()) {
          return;
        }
      }

      this.service.get('qc/method.php?type=getMoaMethodForSpecTest&spec_test_id=' + encodeURIComponent(String(id))).subscribe({
        next: (row: any) => {
          const md = row?.method_data;
          if (md && typeof md === 'object') {
            this.applyMethodPayload(md);
          } else if (methods && typeof methods === 'object' && !Array.isArray(methods)) {
            this.applyMethodPayload(methods);
          }
          this.isView = true;
        },
        error: () => {
          if (methods && typeof methods === 'object' && !Array.isArray(methods)) {
            this.applyMethodPayload(methods);
          }
          this.isView = true;
        },
      });
    }, () => {
      alertify.error('Unable to load this method.');
      this.isView = false;
    });
  }

  hasAnyMethodContent(): boolean {
    return !!(
      this.safetyHtml ||
      this.procedureHtml ||
      this.chromatographicHtml ||
      this.generalInfoList.length ||
      this.purposeList.length ||
      this.scopeList.length ||
      this.definitionList.length ||
      this.testingInstructionList.length ||
      this.procedureList.length ||
      this.associateDocuments.length ||
      this.referenceDocuments.length ||
      this.equipmentList.length ||
      this.balanceList.length ||
      this.chemicalList.length ||
      this.glasswareList.length ||
      this.volumetricList.length ||
      this.mobilePhaseList.length
    );
  }

  /** Safely parse a value that may be a JSON string or already an array/object. */
  private parse(value: any): any {
    if (value === null || value === undefined) {
      return null;
    }
    if (typeof value !== 'string') {
      return value;
    }
    const trimmed = value.trim();
    if (!trimmed) {
      return null;
    }
    try {
      return JSON.parse(trimmed);
    } catch (e) {
      return trimmed;
    }
  }

  /** Returns an array (possibly empty) from a value that may be a JSON string. */
  private toArray(value: any): any[] {
    const parsed = this.parse(value);
    if (Array.isArray(parsed)) {
      return parsed;
    }
    if (parsed && typeof parsed === 'object') {
      return [parsed];
    }
    return [];
  }

  /** Normalises a section into a list of display strings (handles {value} rows and plain strings). */
  private toValueList(value: any): string[] {
    const parsed = this.parse(value);
    const output: string[] = [];
    if (Array.isArray(parsed)) {
      for (const row of parsed) {
        if (row === null || row === undefined) {
          continue;
        }
        if (typeof row === 'string') {
          const text = row.trim();
          if (text) {
            output.push(text);
          }
          continue;
        }
        if (typeof row === 'object') {
          const text = String(
            row['value'] !== undefined ? row['value'] :
            row['discrption'] !== undefined ? row['discrption'] :
            row['description'] !== undefined ? row['description'] :
            row['defination'] !== undefined ? row['defination'] :
            (Object.values(row)[0] ?? '')
          ).trim();
          if (text) {
            output.push(text);
          }
        }
      }
      return output;
    }
    if (typeof parsed === 'string' && parsed.trim()) {
      output.push(parsed.trim());
    }
    return output;
  }

  private toRichHtml(value: any): string {
    const parsed = this.parse(value);
    if (typeof parsed === 'string') {
      const text = parsed.trim();
      if (text && text.toUpperCase() !== 'NA') {
        return text;
      }
    }
    return '';
  }

  viewMethodFile(fileName: any) {
    const safeName = String(fileName || '').trim();
    if (!safeName) {
      alertify.error('File not found');
      return;
    }
    const url = this.service.url + '../../upload/method_documents/' + safeName + '?v=1';
    window.open(url, '_blank');
  }
}
