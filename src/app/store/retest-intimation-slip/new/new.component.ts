import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-retest-intimation-new',
  templateUrl: './new.component.html',
  styleUrls: ['../retest-intimation-slip.shared.css'],
})
export class NewComponent implements OnInit {
  candidates: any[] = [];
  selected: any[] = [];
  loading = false;
  materialType = '';
  intimation_date = '';
  remarks = '';

  constructor(private service: DataAccessService) {
    this.intimation_date = new Date().toISOString().slice(0, 10);
  }

  ngOnInit(): void {
    this.loadCandidates();
  }

  loadCandidates() {
    this.loading = true;
    const q = this.materialType ? '&material_type=' + encodeURIComponent(this.materialType) : '';
    this.service.getJsonArray('store/retest_intimation.php?type=getRetestIntimationCandidates' + q).subscribe({
      next: (rows: any[]) => {
        this.candidates = (rows || []).map((r) => ({ ...r, _selected: false, containers: r.containers || '' }));
        this.selected = [];
        this.loading = false;
      },
      error: () => {
        this.candidates = [];
        this.loading = false;
      },
    });
  }

  toggle(row: any) {
    row._selected = !row._selected;
    this.selected = this.candidates.filter((r) => r._selected);
  }

  save() {
    if (!this.selected.length) {
      alertify.error('Select at least one material.');
      return;
    }
    const payload = {
      intimation_date: this.intimation_date,
      material_type: this.materialType,
      remarks: this.remarks,
      lines: this.selected,
    };
    this.service.post('store/retest_intimation.php?type=saveRetestIntimationSlip', JSON.stringify(payload)).subscribe((res: any) => {
      if (res?.status === 'success') {
        alertify.success(res.msg || 'Intimation slip created.');
        if (res.slip_id) {
          this.service.open('store/retest_intimation.php?type=downloadRetestIntimationSlip&id=' + res.slip_id);
        }
        this.loadCandidates();
        this.remarks = '';
      } else {
        alertify.error(res?.msg || 'Failed to create slip.');
      }
    });
  }
}
