import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-review',
  templateUrl: './review.component.html',
  styleUrls: ['./review.component.css'],
})
export class ReviewComponent implements OnInit {
  isView = false;
  results;
  ctrl_no = '';

  selectedReport = [];
  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit(): void {
    this.getInprocessDept();
  }

  getInprocessDept() {
    this.service
      .get('changecontrol1.php?type=getInprocessDept')
      .subscribe((response) => {
        this.results = response;
      });
  }
  getAprvlCC() {
    this.service
      .get('changecontrol1.php?type=getAprvlCC')
      .subscribe((response) => {
        this.results = response;
      });
  }
  view(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }
  // Checkbox state variables
  engineering = false;
  store = false;
  qc = false;
  qa = false;
  hr = false;
  checkAll1 = false;
  ra = false;
  rnd = false;
  microbiology = false;
  dep_send_count = 0;

  check() {
    if (this.checkAll1 == true) {
      this.engineering = true;
      this.store = true;
      this.qc = true;
      this.qa = true;
      this.hr = true;
      this.ra = true;
      this.rnd = true;
      this.microbiology = true;
      this.dep_send_count = 9;
      console.log('this.dep_send_count :>> ', this.dep_send_count);
    } else {
      // this.quality_head = false;
      this.engineering = false;
      this.store = false;
      this.qc = false;
      this.qa = false;
      this.hr = false;
      this.ra = false;
      this.rnd = false;
      this.microbiology = false;
      this.dep_send_count = 0;
      console.log('this.dep_send_count :>> ', this.dep_send_count);
    }
  }

  engineering_count = 0;
  store_count = 0;
  qc_count = 0;
  qa_count = 0;
  hr_count = 0;
  ra_count = 0;
  rnd_count=0;
  microbiology_count = 0;
  update(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    const total_count =
      (this.engineering ? 1 : 0) +
      (this.qc ? 1 : 0) +
      (this.qa ? 1 : 0) +
      (this.hr ? 1 : 0) +
      (this.store ? 1 : 0) +
      (this.ra ? 1 : 0) +
      (this.rnd ? 1 : 0) +
      (this.microbiology ? 1 : 0);
    console.log('total_count :>> ', total_count);
    let temp = data.value;
    temp['total_count'] = total_count;
      temp['count'] = this.selectedReport['count'];
    // temp['ctrl_no'] = this.selectedReport['ctrl_no'];
    temp['dept_id'] = this.selectedReport['dept_id'];
    this.service
      .post(
        'changecontrol1.php?type=reviewCc&ctrl_no=' +
          this.selectedReport['ctrl_no'],
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status']) {
          alert('Change Control Updated Successfully');
          this.isView = false;
          //  this.getInprocessDept();
        } else {
          alert('Failed: An error occured, please try again!');
        }
      });
  }
}
