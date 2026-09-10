import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

declare let alertify;

@Component({
  selector: 'app-mannual',
  templateUrl: './mannual.component.html',
  styleUrls: ['./mannual.component.css']
})
export class MannualComponent implements OnInit {

  racks;
  lanes;
  materials;
  isNew =  false;

  list = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getLocationByRacks();
    this.getSections();
  }


    sections ;
  getSections() {
    this.service.get('store/location.php?type=getSections').subscribe(response => {
      this.sections = response;
    });
  }
 


  capacity = 0;
  section_name = '';
  laneNO = '';

  getLaneBySection() {
    this.service.get('store/location.php?type=getLaneBySection&section_name='+this.section_name).subscribe(response => {
      this.lanes = response;
    });
  }

  getLaneBySectionForPopoup(section_name) {
    this.service.get('store/location.php?type=getLaneBySection&section_name='+ section_name).subscribe(response => {
      this.lanes = response;
    });
  }

  getRacksByLaneForSearch(laneNO) {
    this.service.get('store/location.php?type=getRacksByLane&laneNO='+laneNO).subscribe(response => {
      this.racks = response;
    });
  }

  getPendingRacksForLocationCreationByLane(laneNO) {

    if(laneNO == 'ADD NEW'){
      this.isNewLane = !this.isNewLane;
    }else{
      this.service.get('store/location.php?type=getPendingRacksForLocationCreationByLane&laneNO='+laneNO).subscribe(response => {
        this.racks = response;
      });
      this.isNewLane = false;
    }

  }



  laneNOFilter = 'ALL';
  rack_noFilter = 'ALL';

 locations;
  getLocationByRacks() {
    this.service.get('store/location.php?type=getLocationByRacks&rack_no='+this.rack_noFilter +'&section_name='+this.section_name+'&laneNO='+this.laneNOFilter).subscribe(response => {
      this.locations = response;
    });
  }


  getAllLocation(){
    this.laneNOFilter = 'ALL';
    this.rack_noFilter = 'ALL';
    this.getLocationByRacks();
  }

  isNewRack = false;

  getCapacityOfRack(rack_no) {

    if(rack_no == 'ADD NEW'){
      this.isNewRack = !this.isNewRack;
    }else{
      if (!rack_no) {
        this.capacity = 0;
        return;
      }
      this.capacity = this.racks.find(rack => rack.rack_no === rack_no)?.capacity || 0;
      this.isNewRack = false;
    }

  }


  generateLocations(data) {
    
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
    this.service.post('store/location.php?type=generateLocations',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.isNew = false;
        this.section_name = temp['section_name'];
        this.laneNOFilter = temp['laneNO'];
        this.rack_noFilter = temp['rack_no'];
        alertify.success(response['message']);
        this.getLocationByRacks();
      } else {
        alertify.error('An error occred, please try again');
      }
    });

  }




  isNewLane = false;


  onLaneChange(data) {

    if(!data.valid){
      alertify.error("All Field Required!!!!!!");
      return;
    }

    let temp =  data.value;
    // Validate with regex: only alphabets (A–Z)
    const validLane = /^[A-Z]+$/.test(temp['laneNO']);
    if (!validLane) {
      alertify.error('Invalid lane code. Please enter alphabets only (A–Z).');
      return;
    }

    // Optional: prevent too long codes
    if (temp['laneNO'].length > 2) {
      alertify.error('Lane code too long. Maximum 2 characters allowed.');
      return;
    }

    // Call backend safely
    this.service.post('store/location.php?type=addNewLane', JSON.stringify(temp)).subscribe({ next: (response) => {
          if (response['status'] === 'success') {
            alertify.success(`Lane "${temp['laneNO']}" added successfully`);
            this.getLaneBySection(); // Refresh list
            this.isNewLane = false;
          } else if (response['status'] === 'duplicate_lane') {
            alertify.warning(`Lane "${temp['laneNO']}" already exists`);
            this.isNewLane = false;
          } else {
            alertify.error(response['message'] || 'Failed to add new lane');
          }
        },
        error: (err) => {console.error('Error adding lane:', err);alertify.error('Server error. Please try again.'); },
      });
    
  }




  saveRack(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    this.service.post('store/location.php?type=saveRack',JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        this.getPendingRacksForLocationCreationByLane(temp['laneNO']);
        this.isNewRack = false;
        alertify.success('Data saved successfully');
      } else {
        alertify.error('An error occred, please try again');
      }
    });
  }

 
 
}
