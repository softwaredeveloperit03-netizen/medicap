  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  declare let alertify;
  @Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;
  operators;
  month='';
  
  
  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.month = this.datePipe.transform(Date.now(), 'yyyy-MM');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit(): void {
      this.getOperators();
      this.getOperations();
  }

  getOperations(){
    this.service.get('engineering/aircompressor.php?type=getOpeartions&month='+this.month).subscribe(response =>{
      this.results = response;
    });
  }

  getOperators(){
    this.service.get('employee.php?type=getUtilitySelectedPersons').subscribe(response =>{
      this.operators = response;
    });
  }
  
  download(){
    this.service.open('engineering/aircompressor.php?type=downloadOpeartions&from_date='+this.from_date+'&to_date='+this.to_date)
  }

  saveOperation(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('engineering/aircompressor.php?type=saveOperation',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getOperations();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }

  }