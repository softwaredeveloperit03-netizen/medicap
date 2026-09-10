import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-operation',
  templateUrl: './operation.component.html',
  styleUrls: ['./operation.component.css'],
  providers: [DatePipe]
})
export class OperationComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;

  operators;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getNitrogenPlant();
    this.getOperators();
  }

  getOperators(){
    this.service.get('employee.php?type=getUtilitySelectedPersons').subscribe(response =>{
      this.operators = response;
    });
  }

  getNitrogenPlant() {
    this.service.get('engineering/nitrogen.php?type=getOperations&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  download() {
    this.service.open('engineering/nitrogen.php?type=downloadOperations&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  saveOperation(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('engineering/nitrogen.php?type=saveOperation', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.getNitrogenPlant();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
}