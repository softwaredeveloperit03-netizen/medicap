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
    this.getArtworkDataLog()
  }

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=getArtWorkStatusLog').subscribe(response=>{
      this.results=response;
    })
   
  }

  
  fileView(url){
    url = this.service.url + '../../upload/artwork/' + url;
    window.open(url, '_blank');
  }


}
