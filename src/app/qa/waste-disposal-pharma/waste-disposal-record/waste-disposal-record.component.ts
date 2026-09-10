import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import {
  EFFECTIVE_DATE,
  FORM_NO,
  MANUAL_MATERIAL,
  MaterialOption,
  REVISION_NO,
  SOP_REF,
  SOURCE_OF_WASTE_OPTIONS,
  WASTE_TYPE_OPTIONS,
  WasteDisposalRow,
  createDefaultWasteRows,
  createEmptyWasteRow,
  defaultFromDate,
  defaultToDate,
  getEmpDisplayName,
  materialLabel,
} from '../waste.utils';

declare let alertify: any;

@Component({
  selector: 'app-waste-disposal-record',
  templateUrl: './waste-disposal-record.component.html',
  styleUrls: ['../waste.shared.css'],
  providers: [DatePipe],
})
export class WasteDisposalRecordComponent implements OnInit {
  formNo = FORM_NO;
  sopRef = SOP_REF;
  revisionNo = REVISION_NO;
  effectiveDate = EFFECTIVE_DATE;
  manualMaterial = MANUAL_MATERIAL;
  sourceOptions = SOURCE_OF_WASTE_OPTIONS;
  wasteTypeOptions = WASTE_TYPE_OPTIONS;

  isLog = false;
  isView = false;
  results: any[] = [];
  selectedRecord: any = null;
  fromDate = '';
  toDate = '';
  maxDate = '';

  recordDate = '';
  selectedSources: string[] = [];
  wasteRows: WasteDisposalRow[] = [];
  materialOptions: MaterialOption[] = [];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private route: ActivatedRoute,
    private datePipe: DatePipe
  ) {
    this.fromDate = defaultFromDate(datePipe);
    this.toDate = defaultToDate(datePipe);
    this.maxDate = this.toDate;
    this.recordDate = this.datePipe.transform(new Date(), 'yyyy-MM-dd') || '';
    this.wasteRows = createDefaultWasteRows(datePipe);
  }

  ngOnInit(): void {
    this.isLog = this.route.snapshot.data['view'] === 'log';
    if (this.isLog) {
      this.getLog();
    } else {
      this.loadMaterialOptions();
    }
  }

  loadMaterialOptions(): void {
    const options: MaterialOption[] = [];
    const addUnique = (items: any[], source: string, prefix: string) => {
      if (!Array.isArray(items)) {
        return;
      }
      items.forEach((item, index) => {
        const name = (item?.product_name || item?.material_name || '').trim();
        if (!name) {
          return;
        }
        const key = `${prefix}_${item?.product_code || item?.material_code || item?.id || index}`;
        if (!options.some((o) => o.key === key)) {
          options.push({
            key,
            label: materialLabel(item, source),
            name,
            source,
          });
        }
      });
    };

    this.service.get('master/product.php?type=getBrandProductsLog').subscribe(
      (products: any) => {
        addUnique(products, 'Product', 'product');
        this.service.get('common.php?type=getProducts').subscribe((fallbackProducts: any) => {
          addUnique(fallbackProducts, 'Product', 'product_fb');
          this.service
            .get('common.php?type=getMaterialsByTypes&material_type=Raw Material')
            .subscribe(
              (materials: any) => {
                addUnique(materials, 'Raw Material', 'raw');
                this.materialOptions = options.sort((a, b) => a.label.localeCompare(b.label));
              },
              () => {
                this.materialOptions = options.sort((a, b) => a.label.localeCompare(b.label));
              }
            );
        });
      },
      () => {
        this.service.get('common.php?type=getProducts').subscribe((fallbackProducts: any) => {
          addUnique(fallbackProducts, 'Product', 'product_fb');
          this.materialOptions = options.sort((a, b) => a.label.localeCompare(b.label));
        });
      }
    );
  }

  isSourceSelected(source: string): boolean {
    return this.selectedSources.includes(source);
  }

  toggleSource(source: string, checked: boolean): void {
    if (checked) {
      if (!this.selectedSources.includes(source)) {
        this.selectedSources.push(source);
      }
    } else {
      this.selectedSources = this.selectedSources.filter((s) => s !== source);
    }
  }

  addRow(): void {
    this.wasteRows.push(createEmptyWasteRow(this.datePipe));
  }

  removeRow(index: number): void {
    if (this.wasteRows.length <= 1) {
      alertify.error('At least one row is required');
      return;
    }
    this.wasteRows.splice(index, 1);
  }

  onMaterialChange(index: number): void {
    const row = this.wasteRows[index];
    if (row.selected_material_key === this.manualMaterial) {
      row.material_mode = this.manualMaterial;
      row.selected_material_key = '';
      row.waste_material_name = '';
      return;
    }
    const selected = this.materialOptions.find((o) => o.key === row.selected_material_key);
    row.material_mode = 'master';
    row.waste_material_name = selected?.name || '';
  }

  stampInitials(index: number): void {
    this.wasteRows[index].initials = getEmpDisplayName();
  }

  getLog(): void {
    this.service
      .get(
        'qa/wasteDisposalPharma.php?type=getWasteDisposalLog&from_date=' +
          this.fromDate +
          '&to_date=' +
          this.toDate
      )
      .subscribe((response: any) => {
        this.results = Array.isArray(response) ? response : [];
      });
  }

  view(record: any): void {
    this.service
      .get('qa/wasteDisposalPharma.php?type=getWasteDisposalById&id=' + record.id)
      .subscribe((response: any) => {
        if (response?.id) {
          this.selectedRecord = response;
          this.isView = true;
        }
      });
  }

  closeView(): void {
    this.isView = false;
    this.selectedRecord = null;
  }

  save(form: NgForm): void {
    if (form.invalid) {
      alertify.error('Please fill required fields');
      return;
    }
    if (!this.selectedSources.length) {
      alertify.error('Please select at least one Source of Waste');
      return;
    }

    const rows = this.wasteRows
      .filter((row) => (row.waste_material_name || '').trim() !== '')
      .map((row) => ({
        waste_material_name: row.waste_material_name.trim(),
        approximate_weight_kg: row.approximate_weight_kg,
        waste_type: row.waste_type,
        initials: row.initials,
        row_date: row.row_date,
        comments: row.comments,
      }));

    if (!rows.length) {
      alertify.error('Please add at least one waste material row');
      return;
    }

    const payload = {
      form_no: this.formNo,
      sop_ref: this.sopRef,
      record_date: this.recordDate,
      source_of_waste: this.selectedSources,
      waste_rows: rows,
    };

    this.service
      .post('qa/wasteDisposalPharma.php?type=saveWasteDisposalRecord', JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Waste disposal record saved successfully');
          this.router.navigate(['/qa/waste-disposal-pharma/log']);
        } else {
          alertify.error(response?.status || 'Failed to save');
        }
      });
  }

  downloadForm(id: number): void {
    this.service.open('qa/wasteDisposalPharma.php?type=downloadWasteDisposalForm&id=' + id);
  }
}
