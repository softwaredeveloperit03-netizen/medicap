import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

 
  vendors;
  units;
  departments
   materials_data: any;
  department: any;
  plant_id;
  show_materials =[];
  selectedMaterial: any;
  quotation_type = 'Local Purchase'




  constructor(private service: DataAccessService,private http: HttpClient, private router: Router) { }

  ngOnInit() {
    this.department = localStorage.getItem('department');  

    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });

    this.getallmaterial();
    this.getManufactures();

   }

  getallmaterial() {
    this.service.get('common.php?type=getallmatdataForIndent').subscribe(response => {
      this.materials_data = response;
    });
  }

 
 
  getManufactures() {
    this.service.get('common.php?type=getManufacturers').subscribe(response => {
      this.vendors = response;
    });
  }


  searchTerm: string;
 
  get filteredItems() {
    return this.materials_data.filter(item => item.material_name.toLowerCase().includes(this.searchTerm?.toLowerCase() || ''));
  }

  selectItem(item: any) {
    this.selectedMaterial = item;
  }

 


  add(){
    console.log(this.selectedMaterial);
    this.show_materials.push(this.selectedMaterial);
    console.log(this.show_materials);
  }
 
  deleteshowmat(index){
    this.final_material.splice(index,1);
  }


  final_material =[];

  addindent(data){

    if (!data.valid) {
      alertify.error('Please enter required field');
      return;
    }
    let temp = data.value;
    temp['department'] = this.department;

    this.final_material.push(temp);
    this.show_materials=[];

  }
 
  save(){

    if (this.final_material.length == 0) {
      alert('Please Add Material!!!!');
      return;
    }

    console.log(this.final_material);
    this.service.post('purchase/indent.php?type=saveIndent', JSON.stringify(this.final_material)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Purchase Requisition records saved successfully');
        this.final_material = [];
        
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
    
  }


}
