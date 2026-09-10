import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;


@Component({
  selector: 'app-preparation',
  templateUrl: './preparation.component.html',
  styleUrls: ['./preparation.component.css']
})
export class PreparationComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  selectedFinalResult=[];

  selectedReport=[];
   file:any
  product_code:any
  material_code:any
    selectedFile: any;
    isNew=false
    View=false
    finalResults: any
    selectedMaterialResult=[]
    developer: any
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.getProduct();
    this.getPrintedMaterials()
    this.getArtworkDataLog()
     //this.getdevloper()
    this.getUnit()

  }
  materials;


  getProduct(){
    this.service.get('qa/artwork.php?type=getProduct').subscribe(response=>{
      this.results = response;
    })
  }
  units;

  getUnit(){
    this.service.get('qa/artwork.php?type=getUnit').subscribe(response=>{
      this.units=response;
    })
  }

  getPrintedMaterials(){
    this.service.get('qa/artwork.php?type=getPrintedMaterials').subscribe(response=>{
      this.materials=response;
    })
  }

  getMaterials(index){
   index=index-1;
   if(index!== -1){
    this.selectedResult=this.results[index];
   }
  }

  

  structureFile:File;

  onFileChange(event) {
    if (event.target.files.length === 1) {
          this.structureFile = event.target.files[0];
    }
  }

  saveForm(data){
  
    let temp = data.value;
    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
 
    if (this.structureFile !== undefined) {
      uploadData.append('structure_file', this.structureFile, this.structureFile.name);
      this.file= this.structureFile.name

    }
  
    this.service.post('qa/artwork.php?type=saveArtwork',uploadData).subscribe(response => {
      alert('Artwork form save Successfully');
      data.resetForm();
      this.isNew = false;
    });
  }
  
  getDetails(index) {
    index=index-1;
    if(index!== -1){
      let materials = this.selectedResult['materials'];
      this.selectedReport = materials[index];
      console.log(this.selectedReport)
    }
  }
  closeNew()
  {
    this.isNew=false
    this.isView=false
  }

  openNew()
  {
    this.isNew=true
    this.isView=true
  }

  view(index)
  {
    console.log("inside view")
  
     this.selectedFinalResult=this.finalResults[index];
     console.log(this.selectedFinalResult);
  
    this.isNew=false
    this.isView=true
    this.View=true

  }
  closeView()
  {
    this.isNew=false
    this.View=false
    this.isView=false
  }

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=getUploadedArtwork').subscribe(response=>{
      this.finalResults=response;
    })
   
  }


  getdevloper(){
    this.service.get('hr/employee.php?type=getartist').subscribe(response=>{
      this.developer=response;
    })
  }


  fileView(url)
  {
  
    url = this.service.url + '../../upload/artwork/' + url;
    window.open(url, '_blank');
  
 
  }
  




}
