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
  testing_type = 0;
  selectedResult;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    // this.getPendingReceiving();
    this.getTestingPersons();
  }
  getData(value){
    if(value=='Production'){
      this.getPendingReceiving();
    }
    else if(value=='Packing'){
      this.getPendingReceiving_PK();
    }
  }
  getPendingReceiving_PK(){
    this.service.get('production/technical.php?type=getPendingReceivingForAllocation').subscribe(response => {
      this.results = response;
    });
  }
  getPendingReceiving(){
    this.service.get('production/technical.php?type=getPendingReceivingForAllocationProd').subscribe(response => {
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
    //this.getTestingPersons();
  }

  submit(data){

    if(!data.valid){
      alertify.error('All Field Required !!!!!!!!');
      return;
    }

    this.service.post('production/technical.php?type=allocateTests&testing_type='+this.testing_type,JSON.stringify(this.selectedResult)).subscribe(response => {
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
