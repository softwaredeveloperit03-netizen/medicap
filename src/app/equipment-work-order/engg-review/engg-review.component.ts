import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-equipment-work-order-engg-review',
  templateUrl: './engg-review.component.html',
  styleUrls: ['../work-order-form.theme.css'],
})
export class EnggReviewComponent implements OnInit {
  department = '';
  results: any[] = [];
  selected: any = null;
  isView = false;
  today = new Date().toISOString().substring(0, 10);
  review: any = {
    gmp_impact: '',
    historical_impact: '',
    qa_approval_required: '',
    reviewed_by: '',
    reviewed_date: '',
  };

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.department = localStorage.getItem('department') || '';
    this.review.reviewed_by =
      localStorage.getItem('emp_name') ||
      localStorage.getItem('username') ||
      localStorage.getItem('emp_id') ||
      '';
    this.review.reviewed_date = this.today;
    this.load();
  }

  load(): void {
    this.service
      .get('engineering/equipment_work_order.php?type=getByStatus&status=TO_ENGG_REVIEW&scope=all')
      .subscribe((response) => {
        this.results = (response as any[]) || [];
      });
  }

  view(item: any): void {
    this.selected = item;
    this.isView = true;
  }

  save(formRef: any): void {
    if (!formRef.valid) {
      alertify.error('Please complete GMP / Historical / QA fields');
      return;
    }
    this.service
      .post(
        'engineering/equipment_work_order.php?type=saveEnggReview&id=' + this.selected.id,
        JSON.stringify(this.review)
      )
      .subscribe((response: any) => {
        if (response?.status === 'success') {
          alertify.success('Engineering review completed — sent to Work Performed');
          this.isView = false;
          this.load();
        } else {
          alertify.error('Failed: ' + (response?.status || 'error'));
        }
      });
  }
}
