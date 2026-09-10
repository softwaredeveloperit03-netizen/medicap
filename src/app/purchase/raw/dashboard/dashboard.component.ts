import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  
  results;
  material_subtype = '';
  material_nature = '';
  grade = '';
  id='';
  uom = '';
  selectedResult=[];
  isEdit=false; 
  grades;
  material_code = '';
  material_name='';
  A_UOM= '';
  materials = [];
  density='';
  order_qty='';
  inventory='';
  inv_unit='';
  location='';
  isView=false;
  gst: Object;
  cas_number='';
  structure_file_path='';
  molecular_weight='';
  molecular_formula='';
  storage_condition='';
  safety_instructions='';
  material_appearance='';
  other_description='';
  packing_requirement='';
  color_index='';
  msds_file_path='';
  equivalancy_factor='';
  lead_time='';
  Unit='';
  pack_size='';
  units;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getMaterialsLog();
    this.service.observableGrade.subscribe(response => {
      this.grades = response;
    });
    this.getGST();
    this.getUnits();
  }
  getUnits() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
  }

  getMaterialsLog(){
    this.service.get('master/material.php?type=getMaterials&material_type=Raw Material&material_subtype=' +  this.material_subtype +'&grade='+this.grade + '&material_nature=' + this.material_nature).subscribe(response => {
      this.results = response;
      this.filterMaterial();
    }); 
  }

  filterMaterial() {
    this.materials = [];
    for (let i = 0; i < this.results.length; i++) {
      let material = this.results[i];
      if (material['material_subtype'].toUpperCase().includes(this.material_subtype.toUpperCase()) && material['material_name'].toUpperCase().includes(this.material_name.toUpperCase()) && material['grade'].toUpperCase().includes(this.grade.toUpperCase()) && material['material_nature'].toUpperCase().includes(this.material_nature.toUpperCase()) && material['material_code'].toUpperCase().includes(this.material_code.toUpperCase())) {
        this.materials[this.materials.length] = material;
      }
    }
  }
  
  downloadReport(){
    this.service.open('qa/material.php?type=materialmasterlog&material_type=Raw Material&material_subtype=' +  this.material_subtype +'&grade='+this.grade + '&material_nature=' + this.material_nature);
  }


  edit(index){
    this.selectedResult=this.materials[index];
    this.material_subtype=this.selectedResult['material_subtype'];
    this.material_nature=this.selectedResult['material_nature'];
    this.material_name=this.selectedResult['material_name'];
    this.uom=this.selectedResult['uom'];
    this.A_UOM=this.selectedResult['alternate_uom'];
    this.grade=this.selectedResult['grade'];
    this.density=this.selectedResult['density'];
    this.order_qty=this.selectedResult['order_qty'];
    this.inventory=this.selectedResult['inventory'];
    this.inv_unit=this.selectedResult['inv_unit'];
    this.location=this.selectedResult['location'];   
    this.cas_number=this.selectedResult['cas_number'];
    this. structure_file_path=this.selectedResult[' structure_file_path'];
    this.molecular_weight=this.selectedResult['molecular_weight'];
    this.molecular_formula=this.selectedResult['molecular_formula'];
    this.storage_condition=this.selectedResult['storage_condition'];
    this.safety_instructions=this.selectedResult['safety_instructions'];
    this.material_appearance=this.selectedResult['material_appearance'];
    this.other_description=this.selectedResult['other_description'];
    this.packing_requirement=this.selectedResult['packing_requirement'];
    this.color_index=this.selectedResult['color_index'];
    this.msds_file_path=this.selectedResult['msds_file_path'];
    this.equivalancy_factor=this.selectedResult['equivalancy_factor'];    
    this.lead_time=this.selectedResult['lead_time'];
    this.Unit=this.selectedResult['unit'];
    this.pack_size=this.selectedResult['pack_size'];
    this.id=this.selectedResult['id'];
    this.isEdit=true;
  }
  view(index){
    this.selectedResult=this.materials[index];
    this.material_subtype=this.selectedResult['material_subtype'];
    this.material_nature=this.selectedResult['material_nature'];
    this.material_name=this.selectedResult['material_name'];
    this.uom=this.selectedResult['uom'];
    this.A_UOM=this.selectedResult['alternate_uom'];
    this.grade=this.selectedResult['gradeName'];
    this.density=this.selectedResult['density'];
    this.order_qty=this.selectedResult['order_qty'];
    this.inventory=this.selectedResult['inventory'];
    this.inv_unit=this.selectedResult['inv_unit'];
    this.location=this.selectedResult['location'];  
    this.cas_number=this.selectedResult['cas_no'];
    this.structure_file_path=this.selectedResult['structure_file_path'];
    this.molecular_weight=this.selectedResult['molecular_weight'];
    this.molecular_formula=this.selectedResult['molecular_formula'];
    this.storage_condition=this.selectedResult['storage_condition'];
    this.safety_instructions=this.selectedResult['safety_instructions'];
    this.material_appearance=this.selectedResult['material_appearance'];
    this.other_description=this.selectedResult['other_description'];
    this.packing_requirement=this.selectedResult['packing_requirement'];
    this.color_index=this.selectedResult['color_index'];
    this.msds_file_path=this.selectedResult['msds_file_path'];
    this.equivalancy_factor=this.selectedResult['equivalancy_factor'];
    this.lead_time=this.selectedResult['lead_time'];
    this.Unit=this.selectedResult['unit'];
    this.pack_size=this.selectedResult['pack_size'];
    this.id=this.selectedResult['id'];
    this.isView=true;
  }

  getGST(){
    this.service.get('common.php?type=getGST').subscribe(response=>{
      this.gst=response;
    });
  }

  editRawMaterial(data){
    let temp=data.value;
    temp['id']=this.id;
     this.service.post('master/material.php?type=updateMaterial&id=' + this.id, JSON.stringify(temp)).subscribe(response=>{
        if(response['status']=='success'){
          alertify.success('Material Updates Successfully');
          data.resetForm();
          this.router.navigate(['/purchase']);
          this.getMaterialsLog();
        }else{
          alertify.error(response['status']);
        }
      });
    }

  deleteRawMaterial(id,status){
    this.service.get('master/material.php?type=deleteMaterial&id='+id+'&status='+status).subscribe(response=>{
      if(response['status']){
        alertify.success('Material Deleted Successuly');
        this.getMaterialsLog();
      }else{
        alertify.error('some error occured');
      }
    });
  }

  AllRecord(){
    this.materials =this.results;
    this.material_subtype='';
    this.material_nature='';
    this.grade = '';
    this.material_code = '';
    this.material_name='';
  }

}
