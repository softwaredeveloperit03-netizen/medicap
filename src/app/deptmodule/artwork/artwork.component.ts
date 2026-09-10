import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-artwork',
  templateUrl: './artwork.component.html',
  styleUrls: ['./artwork.component.css']
})
export class ArtworkComponent implements OnInit {

   
  results: any;
  Department: any;

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
    this.Department = "plant_head_status";
 
    this.getArtworkDataLog()

    
  }

  getArtworkDataLog(){
    this.service.get('qa/artwork.php?type=getArtworkForDeptApproval&dept_name=plant_head_status').subscribe(response=>{
      this.results=response;
    })
  }


     
  fileView(url){
    url = this.service.url + '../../upload/artwork/' + url;
    window.open(url, '_blank');
  }
 




  ApproveArtwork(data,status){

    if (!data.valid) {
      alertify.error('All fields are mandatory');
      return;
    }


    let temp = data.value;

    this.service.post('qa/artwork.php?type=approveArtworkDeptLevel&ID='+this.selectedArtwork['id']+'&status='+status +'&dept_name=plant_head_status',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.getArtworkDataLog()
        this.isDIGI = false;

      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    }); 
  }



  isDIGI = false;
  selectedArtwork =[];

  openView(item){

    this.selectedArtwork = item;
    this.isDIGI = true;
  }



 
 
 


}

