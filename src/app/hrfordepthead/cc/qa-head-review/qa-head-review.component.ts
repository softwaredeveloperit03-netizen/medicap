import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-qa-head-review',
  templateUrl: './qa-head-review.component.html',
  styleUrls: ['./qa-head-review.component.css'],
})
export class QaHeadReviewComponent implements OnInit {
  results: any[] = [];
  isView = false;
  selectedResult: any = {};
  review_action_plan_approval_by_qa = '';

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.loadList();
  }

  loadList() {
    this.service
      .get('changecontrol1.php?type=getCcQAByMeha&plant_id=' + (localStorage.getItem('plant_id') || ''))
      .subscribe((response: any) => {
        this.results = response || [];
      }, () => {
        this.results = [];
      });
  }

  view(i: number) {
    this.selectedResult = this.results[i];
    this.isView = true;
    this.review_action_plan_approval_by_qa = this.selectedResult['APPROVAL_QA'] || '';
  }

  close() {
    this.isView = false;
    this.selectedResult = {};
    this.review_action_plan_approval_by_qa = '';
  }

  save(form: any) {
    const payload = {
      APPROVAL_QA: this.review_action_plan_approval_by_qa,
    };
    this.service
      .post('changecontrol1.php?type=saveQAHeadReview&id=' + this.selectedResult['id'], JSON.stringify(payload))
      .subscribe((response: any) => {
        if (response['status'] === 'success') {
          alertify.success('Saved successfully. Sent to Dept Consent & Review.');
          this.close();
          this.loadList();
        } else {
          alertify.error(response['status'] || 'Failed to save');
        }
      }, () => alertify.error('Error saving'));
  }
}
