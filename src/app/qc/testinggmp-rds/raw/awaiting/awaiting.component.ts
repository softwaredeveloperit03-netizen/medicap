import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { finalize } from 'rxjs/operators';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

interface EquipmentUsageLogEntry {
  testing_no: string;
  test_id: string;
  test_name: string;
  equipment_name: string;
  equipment_code: string;
  start_time: string;
  end_time: string;
  entry_by: string;
  entry_on: string;
}

interface ChemicalUsageLogEntry {
  testing_no: string;
  test_id: string;
  test_name: string;
  chemical_name: string;
  chemical_code: string;
  batch_no: string;
  qty_used: string;
  entry_by: string;
  entry_on: string;
}
  

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})


export class AwaitingComponent implements OnInit {
  private readonly EQUIPMENT_USAGE_LOG_KEY = 'qc_raw_equipment_usage_log';
  private readonly CHEMICAL_USAGE_LOG_KEY = 'qc_raw_chemical_usage_log';
  
  constructor(private service: DataAccessService, private router: Router) { }

 emp_id = localStorage.getItem('emp_id');
  ngOnInit() {
    this.getPendingTestingForms();
    this.emp_id = localStorage.getItem('emp_id');
   }
 

  results;
  material_type = 'Raw Material';
  getPendingTestingForms() {
    this.service.get('qc/testing/raw.php?type=getPendingTestingForms&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }
 

  isTestView = false; 
  isView = false; 
  selectedTesting = {};
  tests;
 
  viewTesting(data) {
    this.loading = true;
    this.loadingMessage = 'Please Wait Tests Are Loading......';
    setTimeout(() => {
      this.getTestByTestingNO(data['testing_no']);
    }, 300);
    this.selectedTesting = data;
    this.isView = true;
    this.isTestView = false;
  }




  loading = false;
  loadingMessage = '';
  
  getTestByTestingNO(testing_no: string) {
    this.service
      .get(
        'qc/testing/raw.php?type=getTestByTestingNO&testing_no=' +
          encodeURIComponent(testing_no)
      )
      .pipe(
        finalize(() => {
          this.loading = false;   // ALWAYS stop loader
        })
      ).subscribe({
        next: (response) => {
          this.tests = response;
        },
        error: (err) => {
          console.error('API error:', err);
        }
      });
  }



  selectedTest = {};

  viewTest(data){
 
    if(data['person'] == localStorage.getItem('emp_id')){
        data['performUSer'] = data['personName'] +' - '+data['person'];
    }else if(data['person_alt'] == localStorage.getItem('emp_id')){
        data['performUSer'] = data['person_altName'] +' - '+data['person_alt'];
    }else{
      alertify.error("This Test Is Not Allocated To You.....");
      return;
    }

    this.selectedTest = data;
    this.isView = false;
    this.isTestView = true;
    this.isMethodView = false;
    this.isProcedure = false;
    this.isEquipment = false;
    this.isChecmical = false;
    this.isSafety = false;
    this.isVolumetric = false;
    this.isTestingInstruction = false;
    this.loadMoaMethodForTest(data);
  }
  

 
 

  observation = "";
  result = 0;

  checkResult(){

    if (this.selectedTest['limit_type'] == 'Range') {

      if(this.result >= Number(this.selectedTest['lower_limit']) && this.result <= Number(this.selectedTest['upper_limit']) ){
        this.observation = "Complies";
      }else{
        this.observation = "Non-Complies";
      }

      this.reason = '';

    }else if (this.selectedTest['limit_type'] == 'Not MoreThan') {
      
      if(this.result <= Number(this.selectedTest['upper_limit']) ){
        this.observation = "Complies";
      }else{
        this.observation = "Non-Complies";
      }
      this.reason = '';
    }else if (this.selectedTest['limit_type'] == 'Not LessThan') {
      
      if(this.result >= Number(this.selectedTest['lower_limit'])){
        this.observation = "Complies";
      }else{
        this.observation = "Non-Complies";
      }

    } 

  }
 
  reason = '';
 
  startTime!: string;
  endTime!: string;

  setTime(type: 'Start' | 'End'){
    const now = new Date();

    const year = now.getFullYear();
    const month = this.pad(now.getMonth() + 1); // months are 0-based
    const day = this.pad(now.getDate());
    const hours = this.pad(now.getHours());
    const minutes = this.pad(now.getMinutes());

    const localDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;

    if (type === 'Start') {
      this.startTime = localDateTime;
    } else {
      this.endTime = localDateTime;
    }
  }

  private pad(value: number): string {
    return value < 10 ? '0' + value : value.toString();
  }
  
 
  temp = {};
  isDIGI = false;
  form;

  openDigiSign(data){

    if (!data.valid) {
      alert('All Field Required!!!!');
      return;
    }
    this.form = data;
    this.temp = data.value;
    this.temp['observation'] = this.observation;
    if(this.temp['reason']=='Other'){
      this.temp['reason'] = this.temp['ifReasoneIsOther'];
    }
    this.temp['reason'] = this.reason;
    this.temp['selectedTestId'] = this.selectedTest['id'];
    this.isDIGI = true;

  }
 

  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    let loginPassward = data.value?.loginPassward;

    this.service.get('login.php?type=checkDigiSIgn&mpin=' + loginPassward +'&emp_id=' + localStorage.getItem('emp_id')).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        data.reset();
        this.saveTestResult();
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
 
  saveTestResult(){
    this.service.post('qc/testing/raw.php?type=saveTestResult',JSON.stringify(this.temp)).subscribe(response => {
      if (response['status']) {
        this.saveMethodUsageLogsToLocal();
        alertify.success('Test Result Saved Successfully......');

        this.isView = true;
        this.isTestView = false;
        this.loading = true;
        this.loadingMessage = 'Please Wait Tests Are Loading......';
        setTimeout(() => {
          this.getTestByTestingNO(this.selectedTesting['testing_no']);
        }, 300);
        this.temp ={};
        this.form.reset();
        this.observation = '';
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }




































    isMethodView = false;
    isProcedure = false;
    isEquipment = false;
    isChecmical = false;
    isSafety = false;
    isVolumetric = false;
    isTestingInstruction = false;
    isLoadingMethod = false;

    safetyHtml = '';
    chromatographicHtml = '';
    volumetricRows: Array<{ name: string; strength: string; unit: string }> = [];
    testingInstructionBlocks: Array<{ heading: string; items: string[] }> = [];

    procedureRows: Array<{ step: string; procedureDescription: string; acceptanceCriteria: string }> = [];

    equipmentRows: Array<{ equipmentName: string; equipmentCode: string; startTime: string; endTime: string }> = [];

    chemicalRows: Array<{ chemicalName: string; chemicalCode: string; batchNo: string; qtyUsed: string; batchOptions: string[] }> = [];

    /** Load MOA method from master/moa-stp-copy (spec_tests + test_methods). */
    private loadMoaMethodForTest(test: Record<string, unknown>): void {
      const specTestId = test['spec_test_id'];
      const specNo = String(this.selectedTesting['specification_no'] || '').trim();
      this.resetMoaMethodView();
      this.isLoadingMethod = true;

      const finish = (row: Record<string, unknown> | null) => {
        this.isLoadingMethod = false;
        if (!row) {
          alertify.warning('MOA method not found for this test. Prepare it in MOA Master first.');
          return;
        }
        const md = (row['method_data'] || {}) as Record<string, unknown>;
        const hasContent = this.hasMoaMethodContent(md) || this.hasLegacyMethodDetails(row);
        const methodStatus = String(
          row['method'] || row['moa_method_status'] || test['moa_method_status'] || ''
        )
          .trim()
          .toLowerCase();
        if (
          !hasContent &&
          (!methodStatus || methodStatus === 'pending' || methodStatus === 'null' || methodStatus === 'undefined')
        ) {
          alertify.warning('MOA method is not prepared yet for this test.');
          return;
        }
        this.applyMoaMethodToPerformView(row);
        this.isMethodView = true;
        this.isSafety = !!this.safetyHtml;
        this.isProcedure = this.procedureRows.length > 0;
        this.isEquipment = this.equipmentRows.length > 0;
        this.isChecmical = this.chemicalRows.length > 0;
        this.isVolumetric = this.volumetricRows.length > 0;
        this.isTestingInstruction = this.testingInstructionBlocks.length > 0;
      };

      const fetchBySpecTestId = (id: string) => {
        this.service
          .get('qc/method.php?type=getMoaMethodForSpecTest&spec_test_id=' + encodeURIComponent(id))
          .subscribe({
            next: (row: Record<string, unknown>) => {
              if (!row || row['id'] == null) {
                finish(null);
                return;
              }
              const md = row['method_data'];
              if (md && typeof md === 'object' && this.hasMoaMethodContent(md as Record<string, unknown>)) {
                finish({ ...row, moa_method_status: test['moa_method_status'] });
                return;
              }
              this.service.get('qc/method.php?type=get_test_data&id=' + encodeURIComponent(id)).subscribe({
                next: (res: Record<string, unknown>) => {
                  const methods = res?.['methods'];
                  if (methods && typeof methods === 'object' && !Array.isArray(methods)) {
                    finish({
                      ...row,
                      method_data: methods,
                      method: row['method'] || test['moa_method_status'],
                    });
                  } else {
                    finish({ ...row, method: row['method'] || test['moa_method_status'] });
                  }
                },
                error: () => finish({ ...row, method: row['method'] || test['moa_method_status'] }),
              });
            },
            error: () => finish(null),
          });
      };

      if (specTestId != null && String(specTestId).trim() !== '') {
        fetchBySpecTestId(String(specTestId));
        return;
      }

      if (!specNo) {
        finish(null);
        return;
      }

      this.service
        .get(
          'qc/method.php?type=getMoaSpecTests&specification_no=' +
            encodeURIComponent(specNo) +
            '&include_method_data=1'
        )
        .subscribe({
          next: (rows: Record<string, unknown>[]) => {
            const norm = (v: unknown) => {
              const s = String(v ?? '').trim();
              return !s || s.toUpperCase() === 'NA' ? 'NA' : s;
            };
            const match = (rows || []).find(
              (r) => r['test'] === test['test'] && norm(r['subtest']) === norm(test['subtest'])
            );
            if (match?.['id'] != null) {
              fetchBySpecTestId(String(match['id']));
            } else {
              finish(match || null);
            }
          },
          error: () => finish(null),
        });
    }

    private resetMoaMethodView(): void {
      this.procedureRows = [];
      this.equipmentRows = [];
      this.chemicalRows = [];
      this.volumetricRows = [];
      this.testingInstructionBlocks = [];
      this.safetyHtml = '';
      this.chromatographicHtml = '';
    }

    private hasText(value: unknown): boolean {
      const s = value == null ? '' : String(value).trim();
      return s !== '' && s.toUpperCase() !== 'NA' && s !== '-';
    }

    private hasMeaningfulProcedureRow(item: Record<string, unknown>): boolean {
      return (
        this.hasText(item['test_description']) ||
        this.hasText(item['description']) ||
        this.hasText(item['acceptance_criteria']) ||
        this.hasText(item['acceptanceCriteria'])
      );
    }

    private hasMeaningfulChemicalRow(item: Record<string, unknown>): boolean {
      return (
        this.hasText(item['chemical_name']) ||
        this.hasText(item['material_name']) ||
        this.hasText(item['name']) ||
        this.hasText(item['chemical_code']) ||
        this.hasText(item['equipment_code']) ||
        this.hasText(item['code'])
      );
    }

    private hasMeaningfulEquipmentRow(item: Record<string, unknown>): boolean {
      return (
        this.hasText(item['equipment_name']) ||
        this.hasText(item['name']) ||
        this.hasText(item['equipment_code']) ||
        this.hasText(item['equipment_id'])
      );
    }

    private hasMoaMethodContent(md: Record<string, unknown> | null | undefined): boolean {
      if (!md || typeof md !== 'object') {
        return false;
      }
      for (const k of ['procedure', 'Procedure', 'Safety', 'chromatographic_conditions']) {
        if (this.hasText(md[k])) {
          return true;
        }
      }
      for (const k of [
        'testinginstruction',
        'equipment_instruments',
        'chemical_reagents',
        'glasswares',
        'balance',
        'volumetric_solutions',
        'phases',
      ]) {
        const v = md[k];
        if (Array.isArray(v) && v.length) {
          return true;
        }
      }
      return false;
    }

    private hasLegacyMethodDetails(row: Record<string, unknown>): boolean {
      const details = row['method_details'];
      return Array.isArray(details) && details.length > 0;
    }

    private applyMoaMethodToPerformView(methodRow: Record<string, unknown>): void {
      const details = Array.isArray(methodRow['method_details']) ? methodRow['method_details'] : [];
      details.forEach((section: Record<string, unknown>) => {
        const opt = String(section['option'] || '').toLowerCase();
        const list = Array.isArray(section['list']) ? section['list'] : [];
        if (opt === 'procedure') {
          list.forEach((item: Record<string, unknown>, idx: number) => {
            if (!this.hasMeaningfulProcedureRow(item)) {
              return;
            }
            this.procedureRows.push({
              step: String(section['name'] || `Step ${idx + 1}`),
              procedureDescription: String(item['test_description'] || item['description'] || ''),
              acceptanceCriteria: String(item['acceptance_criteria'] || item['acceptanceCriteria'] || '-'),
            });
          });
        }
        if (opt === 'chemical') {
          list.forEach((item: Record<string, unknown>) => {
            if (!this.hasMeaningfulChemicalRow(item)) {
              return;
            }
            this.chemicalRows.push({
              chemicalName: String(item['chemical_name'] || item['name'] || '-'),
              chemicalCode: String(item['chemical_code'] || item['code'] || '-'),
              batchNo: '',
              qtyUsed: '',
              batchOptions: [],
            });
          });
        }
        if (opt === 'equipment' || opt === 'equipment_instruments') {
          list.forEach((item: Record<string, unknown>) => {
            if (!this.hasMeaningfulEquipmentRow(item)) {
              return;
            }
            this.equipmentRows.push({
              equipmentName: String(item['equipment_name'] || item['name'] || '-'),
              equipmentCode: String(item['equipment_code'] || item['equipment_id'] || '-'),
              startTime: '',
              endTime: '',
            });
          });
        }
      });

      const md = (methodRow['method_data'] || {}) as Record<string, unknown>;
      if (this.hasText(md['Safety'])) {
        this.safetyHtml = String(md['Safety']);
      }
      if (this.hasText(md['chromatographic_conditions'])) {
        this.chromatographicHtml = String(md['chromatographic_conditions']);
      }
      if (Array.isArray(md['testinginstruction'])) {
        md['testinginstruction'].forEach((ins: Record<string, unknown>) => {
          const items = Array.isArray(ins['testing_instruction'])
            ? ins['testing_instruction'].map((s: Record<string, unknown>) =>
                String(s['testing_instruction'] || '')
              ).filter(Boolean)
            : [];
          if (items.length) {
            this.testingInstructionBlocks.push({
              heading: String(ins['testing_heading'] || 'Testing Instruction'),
              items,
            });
          }
        });
      }
      if (Array.isArray(md['volumetric_solutions'])) {
        md['volumetric_solutions'].forEach((item: Record<string, unknown>) => {
          const name = String(item['solution_name'] || item['name'] || '').trim();
          if (!this.hasText(name)) {
            return;
          }
          this.volumetricRows.push({
            name,
            strength: `${item['percentage'] || ''} ${item['strength'] || ''}`.trim(),
            unit: String(item['unit'] || '-'),
          });
        });
      }
      if (Array.isArray(md['chemical_reagents'])) {
        md['chemical_reagents'].forEach((item: Record<string, unknown>) => {
          if (!this.hasMeaningfulChemicalRow(item)) {
            return;
          }
          this.chemicalRows.push({
            chemicalName: String(item['chemical_name'] || item['material_name'] || '-'),
            chemicalCode: String(item['chemical_code'] || item['equipment_code'] || '-'),
            batchNo: '',
            qtyUsed: '',
            batchOptions: [],
          });
        });
      }
      if (Array.isArray(md['equipment_instruments'])) {
        md['equipment_instruments'].forEach((item: Record<string, unknown>) => {
          if (!this.hasMeaningfulEquipmentRow(item)) {
            return;
          }
          this.equipmentRows.push({
            equipmentName: String(item['equipment_name'] || item['name'] || '-'),
            equipmentCode: String(item['equipment_code'] || item['equipment_id'] || '-'),
            startTime: '',
            endTime: '',
          });
        });
      }
      if (Array.isArray(md['balance'])) {
        md['balance'].forEach((item: Record<string, unknown>) => {
          if (!this.hasMeaningfulEquipmentRow(item)) {
            return;
          }
          this.equipmentRows.push({
            equipmentName: String(item['equipment_name'] || 'Weighing Balance'),
            equipmentCode: String(item['equipment_code'] || '-'),
            startTime: '',
            endTime: '',
          });
        });
      }
      const proc = md['procedure'] || md['Procedure'];
      if (!this.procedureRows.length && this.hasText(proc)) {
        this.procedureRows.push({
          step: 'Procedure',
          procedureDescription: String(proc),
          acceptanceCriteria: '-',
        });
      }
    }

    private saveMethodUsageLogsToLocal(): void {
      const equipmentLogs = this.buildEquipmentUsageLogs();
      const chemicalLogs = this.buildChemicalUsageLogs();
      this.appendUsageLogs<EquipmentUsageLogEntry>(this.EQUIPMENT_USAGE_LOG_KEY, equipmentLogs);
      this.appendUsageLogs<ChemicalUsageLogEntry>(this.CHEMICAL_USAGE_LOG_KEY, chemicalLogs);
    }

    private buildEquipmentUsageLogs(): EquipmentUsageLogEntry[] {
      const testingNo = this.selectedTesting && this.selectedTesting['testing_no'] ? String(this.selectedTesting['testing_no']) : '';
      const testId = this.selectedTest && this.selectedTest['id'] ? String(this.selectedTest['id']) : '';
      const testName = this.selectedTest && (this.selectedTest['testName'] || this.selectedTest['test_name']) ? String(this.selectedTest['testName'] || this.selectedTest['test_name']) : '';
      const emp = localStorage.getItem('emp_id') || '';
      const now = new Date().toISOString();

      return this.equipmentRows
        .filter((eq) => eq.startTime || eq.endTime)
        .map((eq) => ({
          testing_no: testingNo,
          test_id: testId,
          test_name: testName,
          equipment_name: eq.equipmentName,
          equipment_code: eq.equipmentCode,
          start_time: eq.startTime,
          end_time: eq.endTime,
          entry_by: emp,
          entry_on: now
        }));
    }

    private buildChemicalUsageLogs(): ChemicalUsageLogEntry[] {
      const testingNo = this.selectedTesting && this.selectedTesting['testing_no'] ? String(this.selectedTesting['testing_no']) : '';
      const testId = this.selectedTest && this.selectedTest['id'] ? String(this.selectedTest['id']) : '';
      const testName = this.selectedTest['test'] 
      const emp = localStorage.getItem('emp_id') || '';
      const now = new Date().toISOString();

      return this.chemicalRows
        .filter((ch) => ch.batchNo || ch.qtyUsed)
        .map((ch) => ({
          testing_no: testingNo,
          test_id: testId,
          test_name: testName,
          chemical_name: ch.chemicalName,
          chemical_code: ch.chemicalCode,
          batch_no: ch.batchNo,
          qty_used: ch.qtyUsed,
          entry_by: emp,
          entry_on: now
        }));
    }

    private appendUsageLogs<T>(storageKey: string, newRows: T[]): void {
      if (!newRows.length) return;
      let existing: T[] = [];
      try {
        const raw = localStorage.getItem(storageKey);
        existing = raw ? JSON.parse(raw) : [];
        if (!Array.isArray(existing)) existing = [];
      } catch {
        existing = [];
      }
      localStorage.setItem(storageKey, JSON.stringify([...newRows, ...existing]));
    }



 
    searchQuery;
 
    get filteredMaterials(): any[] {
      if (!this.searchQuery || this.searchQuery.trim() === '') {
        return this.results; // If search query is empty or whitespace, return all materials
      }
  
      const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
      return this.results.filter((material) => {
        // Check if any field of the material contains the search query
        return Object.entries(material).some(([key, value]) => {
          if (key === 'entry_date') {
            // Convert the value to a Date object if it's not already
            const dateValue = typeof value === 'string' ? new Date(value) : value;
            // Check if the date value is valid and includes the search query
            return (
              dateValue instanceof Date &&
              dateValue.toISOString().slice(0, 10).includes(query)
            );
          } else {
            // Convert field value to lowercase and check if it includes the search query
            return value && value.toString().toLowerCase().includes(query);
          }
        });
      });
    }

 

}
