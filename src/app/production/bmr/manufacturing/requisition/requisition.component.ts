import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { TINYMCE_SCRIPT_SRC } from '@tinymce/tinymce-angular';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-requisition',
  templateUrl: './requisition.component.html',
  styleUrls: ['./requisition.component.css']
})
export class RequisitionComponent implements OnInit {


  materials;
  types;
  grades;
  clients;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getGrades();
    this.getRawMaterialType();
  }

  
getGrades(){
  this.service.get('master/product.php?type=getGrades').subscribe(response => {
    this.grades = response;
  })
}

getRawMaterialType(){
  this.service.get('master/materialtype.php?type=getRawMaterialtype').subscribe(response => {
    this.types= response;
  });
}

getMaterials(value) {
  this.service.get('common.php?type=getMaterialsByType&material_subtype=' + value).subscribe(response => {
    this.materials = response;
  });
}

  save(data) {
    if (!data.valid) {
      alertify.error('All field are required !');
      return;
    }
    let temp=data.value;
    this.service.post('master/product.php?type=saveProduct', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.router.navigate(['/production/bmr/manufacturing'])
      } else {
        alertify.error(response['status']);
      }
    });
  }
}
