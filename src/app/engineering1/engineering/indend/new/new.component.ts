import { HttpClient } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';
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
  materials_data;
  department: any;
  plant_id;
  show_materials =[];
  selectedMaterial: any;
  quotation_type = 'Local Purchase'


  constructor(private service: DataAccessService,private http: HttpClient, private router: Router) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });

    this.getallmaterial();
    this.getManufactures();
    this.getDepartment();
   }

  getallmaterial() {
    this.service.get('common.php?type=getallmatdata').subscribe(response => {
      this.materials_data = response;
    });
  }

  getDepartment() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
 
  getManufactures() {
    this.service.get('common.php?type=getManufacturers').subscribe(response => {
      this.vendors = response;
    });
  }

 


  add(){
    console.log(this.selectedMaterial);
    this.selectedMaterial['department'] = "Engineering";
    this.show_materials.push(this.selectedMaterial);
    console.log(this.show_materials);
  }
 
  deleteshowmat(index){
    this.show_materials.splice(index,1);
  }
  
  save(){
  
    console.log(this.show_materials);
    this.service.post('purchase/indent.php?type=saveIndentfromdept&from_department=Engineering', JSON.stringify(this.show_materials)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Purchase Requisition records saved successfully');
        this.show_materials = [];
        this.router.navigate(['/engineering/indend/']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
    
  }


}
