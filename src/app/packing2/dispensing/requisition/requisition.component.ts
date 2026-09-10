import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-requisition',
  templateUrl: './requisition.component.html',
  styleUrls: ['./requisition.component.css']
})
export class RequisitionComponent implements OnInit {
 
  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDispensings();
  }

  getPendingDispensings() {
    this.service.get('packing/dispensing.php?type=getPendingPackingDispensing').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  sendRequisition(){
    this.service.post('packing/dispensing.php?type=sendPackingDispensingRequest',JSON.stringify(this.selectedResult)).subscribe(response => {
      if(response['status'] == 'success') {
        alertify.success('Requistion Send Successfully!');
        this.isView = false;
        this.getPendingDispensings();
      } else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

}
