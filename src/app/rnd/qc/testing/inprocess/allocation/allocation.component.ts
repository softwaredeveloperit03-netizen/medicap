import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {

 
  isView = false;
  results;
  employees;

  selectedResult;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingReceiving();
  }

  getPendingReceiving(){
    this.service.get('production/technical.php?type=getPendingReceiving').subscribe(response => {
      this.results = response;
    });
  }

  getTestingPersons() {
    this.service.get('common.php?type=getTestingPersons').subscribe(response => {
      this.employees = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
    this.getTestingPersons();
  }

  submit(){
    this.service.post('production/technical.php?type=allocateTests',JSON.stringify(this.selectedResult)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Person allocated successfully!');
        this.isView = false;
        this.getPendingReceiving();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }



}
