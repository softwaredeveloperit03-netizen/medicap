import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { ReceivingCustomisationLogMeta } from '../receiving-form-customisation.constants';
import { ReceivingFormCustomisationService } from '../receiving-form-customisation.service';

@Component({
  selector: 'app-receiving-form-customisation-view',
  templateUrl: './view.component.html',
  styleUrls: ['./view.component.css'],
})
export class ViewComponent implements OnInit {
  record: ReceivingCustomisationLogMeta | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private service: ReceivingFormCustomisationService
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));
    if (!id) {
      this.back();
      return;
    }
    this.service.getById(id).subscribe((res: any) => {
      this.record = res;
      if (!this.record) {
        this.back();
      }
    }, () => this.back());
  }

  rows(): any[] {
    const f = this.record?.fields;
    return Array.isArray(f) ? f : [];
  }

  getConditionText(row: any): string {
    const key = String(row?.visible_when_key || '').trim();
    const value = String(row?.visible_when_value || '').trim();
    if (!key) {
      return 'Always Visible';
    }
    const source = this.rows().find((r: any) => String(r?.field_key || '') === key);
    const label = String(source?.field_label || key);
    return value ? (label + ' = ' + value) : label;
  }

  back(): void {
    this.router.navigate(['/qa/soft-restriction/receiving-form-customisation']);
  }
}
