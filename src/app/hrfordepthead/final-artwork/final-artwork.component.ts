import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-final-artwork',
  templateUrl: './final-artwork.component.html',
  styleUrls: ['./final-artwork.component.css']
})
export class FinalArtworkComponent implements OnInit {

  
  results: any;
  Department: any;

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.Department= "Quality Assurance";
 
    this.getArtworkDataLog()

    
  }

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=GetQAApproval&dept_name=Quality Assurance').subscribe(response=>{
      this.results=response;
    })
  }


     
  fileView(url){
    url = this.service.url + '../../upload/artwork/' + url;
    window.open(url, '_blank');
  }
 


 isDIGI = false;
  selectedArtwork =[];

  openView(item){

    this.selectedArtwork = item;
    this.isDIGI = true;
  }




  ApproveArtwork(status){

    // if (!data.valid) {
    //   alertify.error('All fields are mandatory');
    //   return;
    // }


     let temp = {};

    this.service.post('qa/artwork.php?type=ArtworkQaApproval&ID='+this.selectedArtwork['id']+'&status='+status ,JSON.stringify(this.selectedArtwork)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.getArtworkDataLog()
        this.isDIGI = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    }); 
  }


 


 
 


}

