import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

declare let alertify: any;


@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  product_code='';
  vendor_no='';
  status='pending';
  products;
  vendors;
  isNew=false
  file:any
  artworkData=[]
    Materials: any
    materialResults=[]
    printingTypes:any
    clientNames: any
    selectedFile: any;
    isrevision=false
    structureFile:File

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
  
    this.getProduct();
    this.getArtworkDataLog()

  }

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=getArtworkDataLog').subscribe(response=>{
      this.results=response;
    })
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }
  opendoc(url){
    url=this.service.url;
    window.open(url+this.selectedResult['file']);
    // this.service.open('qa/artwork.php=getArtworksLog&id='+this.selectedResult['id']);
  }
  getProduct(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    })
  }
  

  getMaterials(value)
  {
    this.service.get('qa/artwork.php?type=getArtworkLogMaterial&productValue='+value).subscribe(response=>{
      this.Materials=response;
      
      
    })

  }

  send_revision(index)
  {
    this.isrevision=true;
    this.isNew=false
   


  }

  View(fileName){
    console.log(fileName)

    // const encodedFileName = encodeURIComponent(fileName.replace(/ /g, '_'));
  
   let  Url = 'https://paperlessgmp.in/php/upload/artwork/'+fileName;
    window.open(Url, '_blank');
     // window.open(this.selectedResult['documents']);
  }
  

  closeNew()
  {
    this.isView=false
    this.isNew=false
    this.getArtworkDataLog()
  }

  onFileChange(event) {
    if (event.target.files.length === 1) {
      this.structureFile = event.target.files[0];

}

const uploadData = new FormData();

if (this.structureFile !== undefined) {
  uploadData.append('structure_file', this.structureFile, this.structureFile.name);
  this.file= this.structureFile.name
}
  
    if (this.selectedFile !== undefined) {
      const file = new File([this.selectedFile], this.selectedFile); 
      uploadData.append('document', file, file.name); 
      }
    this.service.post('qa/artwork.php?type=saveArtorderLogFile', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(' saved successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });



  }
  


   addData(data)
   {
    let temp=data.value
    this.artworkData.push(temp)

  temp['file']=this.file
    this.service.post('qa/artwork.php?type=saveArtorderLog', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Added successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
    // this.artworkData=[]



   }
   openNew()
   {
    this.isView=true
    this.isNew=true

   }
   getPrintingType(value)
   {
    this.service.get('qa/artwork.php?type=getPrintingType&materialValue='+value).subscribe(response=>{
      this.printingTypes=response;
      
      
    })

this.getClientsName(value)
   }

  getClientsName(value)
  {
    this.service.get('qa/artwork.php?type=getClientNamesLog&materialValue='+value).subscribe(response=>{
      this.clientNames=response;
      
      
    })


  }

  send_cc() {
    this.router.navigate(['/qa/cctemporary/initiate/']);
  }
  

}

