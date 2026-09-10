import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  specifications;

  selectedSpec = [];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getCheckedSpecifications();
  }

  getCheckedSpecifications(){
    this.service.get('qc/specification/water.php?type=getCheckedSpecifications').subscribe(response => {
      this.specifications = response;
    });
  }

  updatePackingMaterial(status) {
    this.service.get('qc/specification/packing.php?type=checkSpecification&id=' + this.selectedSpec['id'] + '&status='+ status).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getCheckedSpecifications();
      } else{
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

}
