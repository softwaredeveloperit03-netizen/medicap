import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
 

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArtworksLog();
  }
  getArtworksLog(){
    this.service.get('qa/artwork.php?type=getUploadedDesigns').subscribe(response=>{
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
  update(status) {
    this.service.get('qa/artwork.php?type=updateArtworkDesign&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Record updated successfully');
        this.isView = false;
        this.getArtworksLog();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}
