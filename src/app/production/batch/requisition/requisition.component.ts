import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-requisition',
  templateUrl: './requisition.component.html',
  styleUrls: ['./requisition.component.css']
})
export class RequisitionComponent implements OnInit {

  products;
  results;
  selectedResult = [];
  isNew = false;

  data = [];
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getPendingDispensing();
  }

  getPendingDispensing() {
    this.service.get('production/dispensing.php?type=getPendingDispensing').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    //this.data = JSON.parse(this.selectedBMR['data']);
    this.isNew = true;
  }

  work(id) {
    this.router.navigate(['/batch/work/' + id]);
  }

  stage(id, stage) {
    if (stage == 'DISPENSING') {
      this.router.navigate(['/batch/dispensing/' + id]);
    } else {
      this.router.navigate(['/batch/new/' + id]);
    }
  }

  callforDispensing(index) {
    let stages = this.selectedResult['stages'];
    let stage = stages[index];
    this.data['status'] = 'inprocess';
    this.service.post('bmr.php?type=callforDispensing&bp_no=' + this.selectedResult['no'] + '&stage=' + stage['id'], JSON.stringify(this.data)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Dispensing Request has been send to Store Department');
        this.isNew = false;
        this.getPendingDispensing();
      } else {
        alert('An error occured, please try again!');
      }
    });
  }

}
