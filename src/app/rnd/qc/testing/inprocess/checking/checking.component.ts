import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  isTest = false;
  results;
  tests;

  selectedResult = [];
  selectedTesting = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessTestings();
  }

  getInprocessTestings(){
    this.service.get('production/technical.php?type=getInprocessTestings').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.tests = this.selectedResult['tests'];
    this.isView = true;
  }

  viewTest(index){
    this.selectedTesting = this.tests[index];
    this.isView = true;
    this.isTest = true;
  }

  update(status) {
    this.service.get('production/technical.php?type=checkTesting&status=' + status + '&id=' + this.selectedTesting['id'] + '&ti_no=' + this.selectedResult['ti_no']).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data updated Successfully!');
        this.isTest = false;
        this.isView = false;
        this.getInprocessTestings();
      }else{
        alertify.error('Failed an error occured,please try again!');
      }
    });
  }

}
