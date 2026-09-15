import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-planning-batch-formula',
  templateUrl: './batch-formula.component.html',
  styleUrls: ['./batch-formula.component.css'],
})
export class BatchFormulaComponent implements OnInit {
  results: any[] = [];
  isLog_view = false;
  is_Bfr_view = false;
  selectedResult: any = {};
  selectedBfr: any = {};
  bfr_list: any[] = [];
  productName = '';
  plant_type: any;
  plant_id: any;
  groupedMaterials: { [key: string]: any[] } = {};
  primary_pm_list: any[] = [];
  consumeableMaterial: any[] = [];
  packing_List_All: any[] = [];
  raw_materials: any[] = [];

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.loadLog();
  }

  private hasBomBatchSize(row: any): boolean {
    if (row?.bom_batch_size == null || String(row.bom_batch_size).trim() === '') {
      return false;
    }
    const n = Number(row.bom_batch_size);
    return !isNaN(n) && n > 0;
  }

  private applyBatchSizeFilter(rows: any[]): any[] {
    return (Array.isArray(rows) ? rows : []).filter((row) =>
      this.hasBomBatchSize(row)
    );
  }

  loadLog(): void {
    this.service
      .getJsonArray('planning/raw.php?type=get_batch_formula_log')
      .subscribe((response) => {
        this.results = this.applyBatchSizeFilter(response);
      });
  }

  searchLog(productName: string): void {
    this.service
      .getJsonArray(
        'planning/raw.php?type=get_batch_formula_log1&productName=' +
          encodeURIComponent(productName || '')
      )
      .subscribe((response) => {
        this.results = this.applyBatchSizeFilter(response);
      });
  }

  private parseJsonArray(value: any): any[] {
    if (value == null || value === '') {
      return [];
    }
    if (Array.isArray(value)) {
      return value;
    }
    if (typeof value === 'string') {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  private parsePackingConfiguration(value: any): any[] {
    return this.parseJsonArray(value);
  }

  viewBfr(index: number): void {
    this.groupedMaterials = {};
    this.selectedResult = this.results[index];
    this.raw_materials = this.parseJsonArray(this.selectedResult['raw_materials']);
    this.bfr_list = this.selectedResult['bfr_records'] || [];
    this.primary_pm_list = this.parseJsonArray(this.selectedResult['primary_pm_list']);
    this.consumeableMaterial = this.parseJsonArray(
      this.selectedResult['consumeableMaterial']
    );
    this.packing_List_All = this.parsePackingConfiguration(
      this.selectedResult['packing_configuration']
    );
    this.isLog_view = true;
    this.is_Bfr_view = false;
  }

  viewBfrRecord(index: number): void {
    this.selectedBfr = this.bfr_list[index];
    this.raw_materials = this.parseJsonArray(this.selectedBfr['raw_materials']);
    this.groupedMaterials = {};
    this.is_Bfr_view = true;
    this.isLog_view = false;
  }

  showHomePage(): void {
    this.is_Bfr_view = false;
    this.isLog_view = false;
    this.primary_pm_list = [];
    this.consumeableMaterial = [];
    this.packing_List_All = [];
  }

  closeBfrview(): void {
    this.is_Bfr_view = false;
    this.isLog_view = true;
  }

  download(): void {
    this.service.open(
      'production/unitformula.php?type=downloadUnitFormla&id=' +
        this.selectedResult['id']
    );
  }
}
