import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  
  isView = false;
  specifications;

  selectedSpec = [];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingSpecifications();
  }

  getPendingSpecifications(){
    this.service.get('qc/specification/retest.php?type=getPendingSpecifications').subscribe(response => {
      this.specifications = response;
    });
  }

  updateRetestMaterial(status) {
    this.service.get('qc/specification/retest.php?type=checkSpecification&id=' + this.selectedSpec['id'] + '&status='+ status).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success(this.service.t('common.updatedSuccess'));
        this.isView = false;
        this.getPendingSpecifications();
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
