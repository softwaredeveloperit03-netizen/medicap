import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-material',
  templateUrl: './material.component.html',
  styleUrls: ['./material.component.css']
})
export class MaterialComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  material_type='Packing Material';
  status='';
  material_subtype='';
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPackingMaterial();
  }
  // getPackingMaterial(){
  //   this.service.get('common.php?type=getPackingMaterials&material_type='+this.material_type+'&status='+this.status+ '&material_subtype'+this.material_subtype).subscribe(response=>{
  //     this.results=response;
  //   });
  // }

  getPackingMaterial(){
    this.service.get('qa/material.php?type=getMaterialsLog&material_type='+this.material_type +'&material_subtype='+this.material_subtype +'&status='+this.status).subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
}
