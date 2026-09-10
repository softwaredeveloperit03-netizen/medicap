import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
specification_type: any;
toggleShow() {
throw new Error('Method not implemented.');
}
  packing_requirement;
  structureFile: File;
  msdsFile: File;
  storage_location;
  factor_status :"No";
  category='Active'; 
  equivalancy_applicable='';
  grades;
  storage_condition;
  grade;
  lead_time;
  isGrades;
  other_description;
  color_index;
  msds_file_pat: any;
  safety_instructions;
  types;
  ce_number;
  packSizeList=[];
  units;
  gst: Object;
  plant_type='';
  gst_id=0;
  material_sub_type_id=0;
msds_file_path: any;
material_appearance;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getMaterialType();
    this.getGST();
    this.getUnits();
    this.plant_type = this.service.getPlantConfigFields('plant_type');
    this.getGrades();
  }

  getGST(){
    this.service.get('common.php?type=getGST').subscribe(response=>{
      this.gst=response;
    });
  }
  
  getMaterialType(){
    this.service.get('master/materialtype.php?type=getRawMaterialtype').subscribe(response => {
      this.types= response;
    });
  }

  numberOnly(event): boolean {
    const charCode = (event.which) ? event.which : event.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
      return false;
    }
    return true;

  }
  setSubMaterialType(form,index){
    // let data:any = form;
     console.log(form.form.value.material_subtype);
    this.material_sub_type_id = form.form.value.material_subtype.id;
    
  }
  setGst(index){
    this.gst_id = this.gst[index]['id'];
  }
  onFileChanged(event, id) {
    if (event.target.files.length === 1) {
      switch (id) {
        case 1:
          this.structureFile = event.target.files[0];
          break
        case 2:
          this.msdsFile = event.target.files[0];
          break 

      }

    }
  }
  getUnits() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }
  deletePackSize(index){
    this.packSizeList.splice(index);
  }
  addPackSize(data){
    if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
    this.packSizeList.push(data.value);
    data.reset();
  }

  addMaterial(data) {
    if (!data.valid) {
     
      alertify.error('Please Enter required Field');
      return;
    }
    const uploadData = new FormData();
    let temp= data.value;
    temp['material_type'] = "Raw Material";
    temp['gst_id'] = this.gst_id;
    temp['pack_size'] = this.packSizeList;
    temp['material_sub_type_id'] = this.material_sub_type_id;
    temp['material_subtype'] = temp.material_subtype.material_subtype;
  
    if (this.structureFile !== undefined) {
      uploadData.append('structure_file', this.structureFile, this.structureFile.name);
    }
    if (this.msdsFile !== undefined) {
      uploadData.append('msds_file', this.msdsFile, this.msdsFile.name);
    }
    uploadData.append('data', JSON.stringify(temp));
    this.service.post('master/material.php?type=saveMaterial', uploadData).subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('Record Inserted Successfully');
        this.router.navigate(['/master/material/raw']);
      } else {
      
        alertify.error(response['status']);
      
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  addGrades(value) {
    if (value == 'Add New') {
      this.isGrades = true;
    } else {
      this.isGrades = false;
    }
  }


  saveGrades(data) {
    if (!data.valid) {
      alertify.error('All field are required!');
      return;
    }
    this.service.post('master/product.php?type=saveGrades', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isGrades = false;
        this.getGrades();
      } else {
        alertify.error(response['status']);
      }
    });

  }
  




  getGrades(){
    this.grades =[];
    this.service.get('master/product.php?type=getGrades').subscribe(response => {
      this.grades = response;
    
      this.service.observableGrade.subscribe(response => {
        this.grades = response;
      });
    
    })
  }

}
