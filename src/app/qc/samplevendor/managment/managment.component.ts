import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';


@Component({
  selector: 'app-managment',
  templateUrl: './managment.component.html',
  styleUrls: ['./managment.component.css']
})
export class ManagmentComponent implements OnInit {
  vendorM;
  material_type = '';
  materials;
  selectedMaterial;
    selected_master_type: any;
    sub_types: any[];
    material_sub_type_id: any;

  constructor(private service: DataAccessService,private http: HttpClient, private router: Router) { }


  ngOnInit() {
 
    this.getMaterials();
   }
 
 
  getMaterials() {
    this.materials = [];
     {
      this.service.get('common.php?type=getMaterialsByTypeMangment').subscribe(response => {
        this.materials = response;
      });
    } 
  }
selected_material_type=[]
selected_material_Subtype=[]
  getSubMaterials(index){
    index = index - 1;
    this.selected_material_type = this.materials[index].material_subtypes;
  }
  getMaterialsData(index){
    index = index - 1;
    this.selected_material_Subtype = this.selected_material_type[index].materials;
  }
  selectMaterial(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedMaterial = this.selected_material_Subtype[index];
      // this.getVendors(this.selectedMaterial['material_code'])
    } else {
      this.selectedMaterial = [];
    }
  }

  saveGatepass(data){
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

   

  }

}
