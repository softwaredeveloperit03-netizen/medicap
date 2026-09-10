import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-shade',
  templateUrl: './shade.component.html',
  styleUrls: ['./shade.component.css']
})
export class ShadeComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];


  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getShade();
  }
  getShade(){
    this.service.get('qa/artwork.php?type=getApprovedPrintings').subscribe(response=>{
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
     //this.service.open('qa/artwork.php=getArtworksLog&id='+this.selectedResult['id']);
  }
  openFile(url){
    url=this.service.url;
    window.open(url+this.selectedResult['design_file']);
  }
  printingFile(url){
    url=this.service.url;
    window.open(url+this.selectedResult['printing_file']);
  }
 
}
