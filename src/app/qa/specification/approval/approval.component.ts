import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 
@Component({
selector: 'app-approval',
templateUrl: './approval.component.html',
styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  specifications;
  c_material_type;
  selectedSpec = [];
  constructor(private service: DataAccessService) { }
  plant_id:any;
  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
 
    this.getCheckedSpecifications();
}
  getCheckedSpecifications() {
    this.service.get('qa.php?type=getCheckedSpecifications&spec_type='+this.c_material_type+'&status=qa_reviewed').subscribe(response => {
      this.specifications = response;
    });
  }

  updateRawMaterial(status) {
    this.service.get('qa.php?type=approveSpecification&id=' + this.selectedSpec['id'] + '&status='+ status).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Updated Successfully');
        this.isView = false;
        this.getCheckedSpecifications();
      }else{
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  viewSpecification(index) {
    this.isView = true;
    this.selectedSpec = this.specifications[index];
  }

}
