import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {
  results;
  selectedStability = [];
  isView = false;

  isAllocate = false;

  selectedInterval;
  selectedCondition;

  condition = [];
  interval = [];
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getStabilities();
  }

  getStabilities() {
    this.service.get('stability.php?type=getPendingStabilityAllocation').subscribe(response => {
      this.results = response;
    });
  }

  viewProtocol(index) {
    this.selectedStability = this.results[index];

    let conditions = this.selectedStability['conditions'];
    for (let i = 0; i < conditions.length; i++) {
      let condition = conditions[i];

      let intervals = condition['interval'];

      for (let p = 0; p < intervals.length; p++) {
        let interval = intervals[p];
        let  date = new Date();

        let temp_date = new Date();
        temp_date.setDate(date.getDate() - 7);

        let dateFrom = temp_date.getFullYear() + "-" + (temp_date.getMonth()+1) + "-" + temp_date.getDate();
        let dateTo = date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
        let dateCheck = interval['date'];

        let d1 = dateFrom.split("-");
        let d2 = dateTo.split("-");
        let c = dateCheck.split("-");

        let from = temp_date;
        let to   = new Date();
        let check = new Date(c[0], parseInt(c[1])-1, c[2]);

        if (check >= from && check <= to) {
          if (interval['status'] == 'pending') {
            interval['status'] = 'today';
          }
        }
        intervals[p] = interval;
      }
      condition['interval'] = intervals;
      conditions[i] = condition;
    }
    this.selectedStability['conditions'] = conditions;
    this.isView = true;
  }

  viewPersons(j, index) {
    let conditions = this.selectedStability['conditions'];
    this.condition = conditions[index];
    let intervals = this.condition['interval'];
    this.interval = intervals[j];
    this.interval['status'] = 'allocate';
    this.interval['withdrawal_by'] = localStorage.getItem('emp_id');
    this.interval['withdrawal_date'] = new Date().toLocaleDateString('en-CA');
    this.interval['batches'] = this.condition['batches'];
    this.selectedCondition = index;
    this.selectedInterval = j;
    this.getQAOfficers();
    this.isAllocate = true;
  }

  persons;
  getQAOfficers() {
    this.service.get('stability.php?type=getQAOfficers').subscribe(response => {
      this.persons = response;
    });
  }

  allocateInterval(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let conditions = this.selectedStability['conditions'];
    this.condition = conditions[this.selectedCondition];
    let intervals = this.condition['interval'];
    intervals[this.selectedInterval] = this.interval;
    this.condition['interval'] = intervals;
    conditions[this.selectedCondition] = this.condition;
    this.selectedStability['conditions'] = conditions;

    this.service.post('stability.php?type=allocateIntervalSamplingPerson&stability_no=' + this.selectedStability['id'] + '&condition=' + this.selectedCondition + '&interval=' + this.selectedInterval + '&product_code=' + this.selectedStability['product_code'] + '&specification_no=' + this.selectedStability['specification_no'], JSON.stringify(this.selectedStability['conditions'])).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Interval Testing person allocated successfully');
        this.isView = false;
        this.isAllocate = false;
        this.selectedInterval;
        this.selectedCondition;
        this.condition = [];
        this.interval = [];
        this.getStabilities();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
