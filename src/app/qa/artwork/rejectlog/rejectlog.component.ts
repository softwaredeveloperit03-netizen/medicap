import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-rejectlog',
  templateUrl: './rejectlog.component.html',
  styleUrls: ['./rejectlog.component.css']
})
export class RejectlogComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArtworkDataLog()
  }

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=getRejectedData').subscribe(response=>{
      this.results=response;
    })
   
  }
  fileView(fileName)
  {
  
  
  
   let  Url = 'https://paperlessgmp.in/php/upload/artwork/'+fileName;
    window.open(Url, '_blank');
  }


}
