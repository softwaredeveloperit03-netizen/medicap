import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-artworkinactive',
  templateUrl: './artworkinactive.component.html',
  styleUrls: ['./artworkinactive.component.css']
})
export class ArtworkinactiveComponent implements OnInit {

  results;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getArtworkDataLog()
  }
 

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=getInActiveArtwork').subscribe(response=>{
      this.results=response;
    })
  }

  fileView(url){
    url = this.service.url + '../../upload/artwork/' + url;
    window.open(url, '_blank');
  }

}
