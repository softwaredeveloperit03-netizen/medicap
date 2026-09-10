import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;

  from_date = '';
  to_date = '';
  max_date = '';
  units; 
  company_unit='';
  product_type = '';
  isView=false;
  selectedResult=[];

  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getPendingIntimations();
  }

  getPendingIntimations() {
    this.service.get('production/bmr/sampling.php?type=getPendingIntimations').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  updatesampling(status){
    this.service.get('production/bmr/sampling.php?type=checkIntimation&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status']) {
        alertify.success('sampling Updated Successfully');
        this.isView = false;
        this.getPendingIntimations();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  

}
