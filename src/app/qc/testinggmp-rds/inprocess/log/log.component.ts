import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

 

  isView = false;
  isTest = false;
  specifications;
  tests;
  grades;

  selectedResult = [];
  selectedTesting = [];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getInprocessTestings();
  }

  getInprocessTestings(){
    this.service.get('production/technical.php?type=getTestingsLog').subscribe(response => {
      this.specifications = response;
    });
  }


  getColor(testStatus: string): string {
    switch (testStatus) {
        case 'tested':
            return 'rgb(245, 64, 51)';
        case 'retested':
            return 'rgb(124, 243, 87)';
        default:
            return '';
    }
}

 
  view(index){
    this.selectedTesting = this.specifications[index];
    this.tests = this.selectedTesting['tests'];
    this.selectobservation = this.tests[index];
    for (let i = 0; i < this.tests.length; i++) {
      let test = this.tests[i];
      test['correct_result'] = test['result'];
      this.tests[i] = test;
    }

    let flag = 0;

    this.tests.forEach((element, index) => {
      if (element['observation'] !== "complies" && element['test_status'] !== "tested" ) {
        flag = 1;
        this.tests[index]['error_type'] = element['checker_action'];
      }else{
        if (element['test_status'] == "tested" ) {
          
          this.tests[index]['error_type'] = element['checker_action'];
        }else{
          this.tests[index]['error_type'] = 'NA';
        }
      }
    });

    if (flag == 1) {
      this.isapprove = false;
    } else {
      this.isapprove = true;
    }

    this.isView = true;
  }
  isapprove = false;
  selectobservation;
 

  download(){
    this.service.open('production/technical.php?type=downloadTestingsLog');
  }
}
