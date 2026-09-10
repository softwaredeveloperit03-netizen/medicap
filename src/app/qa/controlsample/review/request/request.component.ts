import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

  isView = false;
  samples;
  employees;
  

  selectedResult = [];
  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit() {
    this.getControlsamples();
    this.getEmployes();
  }

  getEmployes(){
    this.service.get('employee.php?type=getQAPersons').subscribe(response => {
      this.employees = response;
    });
  }
  getControlsamples() {
    this.service.get('qa/controlsample.php?type=getControlSamples').subscribe(response => {
      this.samples=response;
     
    });
  }
  getprint() {
    this.service.open('qa/controlsample.php?type=getControlSamples_log');
  }
  


  view(index){
    this.selectedResult = this.samples[index];
    this.isView = true;
  }

  saveForm(data){
    if(!data.valid){
      alert('All fields are requried!');
      return;
    }
    this.service.post('qa/controlsample.php?type=saveControlSampleReview&id=' + this.selectedResult['id'], JSON.stringify(data.value)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Saved Successfully!')
        this.isView = false;
        this.getControlsamples();
      }else{
        alert('An Error occured, Please try again!');
      }
    });
  }

  close() {
    this.router.navigate(['/controlsample']);
  }
}
