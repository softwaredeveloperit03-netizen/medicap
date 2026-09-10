import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-correction',
  templateUrl: './correction.component.html',
  styleUrls: ['./correction.component.css'],
})
export class CorrectionComponent implements OnInit {
  plant_id = localStorage.getItem('plant_id');
  isView = false;
  isEdit = false;
  loading = false;
  results: any[] = [];
  selectedResult: any = null;
  spec_type = 'Raw Material';
  searchQuery = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.plant_id = localStorage.getItem('plant_id');
    this.getRejectedSpecifications();
  }

  getRejectedSpecifications(): void {
    const effectiveType = String(this.spec_type || '').trim() || 'Raw Material';
    this.loading = true;
    this.service
      .get(
        'qc/specification/raw.php?type=getRejectedSPecificationByTypeAndStatus&spec_type=' +
          encodeURIComponent(effectiveType)
      )
      .subscribe(
        (response) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        () => {
          this.results = [];
          this.loading = false;
        }
      );
  }

  onSpecTypeChange(value: string): void {
    this.spec_type = String(value || '').trim() || 'Raw Material';
    this.isView = false;
    this.isEdit = false;
    this.selectedResult = null;
    this.searchQuery = '';
    this.getRejectedSpecifications();
  }

  view(data: any): void {
    this.selectedResult = JSON.parse(JSON.stringify(data || {}));
    this.isView = true;
    this.isEdit = false;
  }

  edit(data: any): void {
    this.selectedResult = JSON.parse(JSON.stringify(data || {}));
    if (!Array.isArray(this.selectedResult.spectTests)) {
      this.selectedResult.spectTests = [];
    }
    this.isView = true;
    this.isEdit = true;
  }

  closeView(): void {
    this.isView = false;
    this.isEdit = false;
    this.selectedResult = null;
  }

  saveCorrection(): void {
    if (!this.selectedResult || !this.selectedResult.id) {
      alertify.error('No specification selected.');
      return;
    }
    this.service
      .post('qc/specification/raw.php?type=saveCorrectionSpecification', JSON.stringify(this.selectedResult))
      .subscribe((response) => {
        if (response && response['status'] === 'success') {
          alertify.success('Specification corrected and sent for review');
          this.closeView();
          this.getRejectedSpecifications();
        } else {
          alertify.error('Failed: ' + (response?.['status'] || response?.['message'] || 'Please try again'));
        }
      });
  }

  get filteredMaterials(): any[] {
    if (!Array.isArray(this.results)) {
      return [];
    }
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((material) =>
      Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date' || key === 'check_date' || key === 'approve_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return dateValue instanceof Date && !isNaN(dateValue.getTime()) && dateValue.toISOString().slice(0, 10).includes(query);
        }
        return value && value.toString().toLowerCase().includes(query);
      })
    );
  }
}
