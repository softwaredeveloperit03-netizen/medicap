import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sample-charging',
  templateUrl: './sample-charging.component.html',
  styleUrls: ['./sample-charging.component.css']
})
export class SampleChargingComponent implements OnInit {
  results;
  selectedStability = [];
  isView = false;

  isAllocate = false;

  selectedInterval;
  selectedCondition;
  selectedBatch;
  interval_date = '';
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getStabilities();
  }

  getStabilities() {
    this.service.get('stability.php?type=getStabilities').subscribe(response => {
      this.results = response;
    });
  }

  viewProtocol(index) {
    this.selectedStability = this.results[index];
    let batches = this.selectedStability['batches'];
    for (let i = 0; i < batches.length; i++) {
      let batch = batches[i];
      let conditions = batch['conditions'];
      for (let j = 0; j < conditions.length; j++) {
        let data = conditions[j];
        let intervals = data['interval'];
        for (let p = 0; p < intervals.length; p++) {
          let interval = intervals[p];

          if (interval['status'] !== 'allocate') {
            let  date = new Date();
            let todaysdate = date.getFullYear() + "-" + (date.getMonth()+1) + "-" + date.getDate();
            if (interval['date'] == todaysdate) {
              interval['status'] = 'today';
            }
          }
          intervals[p] = interval;
        }
        data['interval'] = intervals;
      }
      batch['conditions'] = conditions;
      batches[i] = batch;
    }
    this.selectedStability['batches'] = batches;
    this.isView = true;
  }

  viewPersons(p, j, index, date) {
    this.selectedBatch = index;
    this.selectedInterval = p;
    this.selectedCondition = j;
    this.interval_date = date;
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
      alert('All fields are required!');
      return;
    }
    let temp = data.value;

    let batchs = this.selectedStability['batches'];
    let batch = batchs[this.selectedBatch];
    let conditions = batch['conditions'];
    let condition = conditions[this.selectedCondition];
    let intervals = condition['interval'];
    let interval = intervals[this.selectedInterval];
    interval['status'] = 'allocate';
    interval['person'] = temp['sampling_person'];

    intervals[this.selectedInterval] = interval;
    condition['interval'] = intervals;
    conditions[this.selectedCondition] = condition;
    batch['conditions'] = conditions;
    batchs[this.selectedBatch] = batch;
    this.selectedStability['batches'] = batchs;

    this.service.post('stability.php?type=allocateIntervalSamplingPerson&stability_no=' + this.selectedStability['id'] + '&batch=' + this.selectedBatch + '&condition=' + this.selectedCondition + '&interval=' + this.selectedInterval, JSON.stringify(this.selectedStability['batches'])).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Interval Sampling person allocated successfully');
        this.isView = false;
        this.isAllocate = false;
        this.selectedBatch;
        this.selectedInterval;
        this.selectedCondition;
        this.interval_date = '';
        this.getStabilities();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
