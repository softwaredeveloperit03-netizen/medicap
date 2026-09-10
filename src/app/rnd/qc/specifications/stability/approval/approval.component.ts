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

  ngOnInit() {
    this.getCheckedSpecifications();
  }

  getCheckedSpecifications() {
    this.service.get('qc/specification/stability.php?type=getCheckedSpecifications').subscribe(response => {
      this.specifications = response;
    });
  }

  updateStabilityMaterial(status) {
    this.service.get('qc/specification/stability.php?type=approveSpecification&id=' + this.selectedSpec['id'] + '&status='+ status).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getCheckedSpecifications();
      }else{
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

}
