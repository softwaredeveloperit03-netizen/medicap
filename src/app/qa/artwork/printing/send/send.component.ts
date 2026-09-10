import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-send',
  templateUrl: './send.component.html',
  styleUrls: ['./send.component.css']
})
export class SendComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArtworksLog();
  }
  getArtworksLog(){
    this.service.get('qa/artwork.php?type=getPendingPrinting').subscribe(response=>{
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
  sendforPrinting(){
    this.service.get('qa/artwork.php?type=sendforPrinting&id=' + this.selectedResult['id']).subscribe(response=>{
      alert("send Successfully");
      this.isView=false;
      this.getArtworksLog();
    });
  }


}
