import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

  isView = false;
  results;

  selectedReport = [];
  total = 0;
  damages = [];
  checkPointData ;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDamages();
    this.getCheckPointData1();
  }


  docFIle1: File | null = null;
  previewUrl1: string | ArrayBuffer | null = null;
  docFIle2: File | null = null;
  previewUrl2: string | ArrayBuffer | null = null;
 
 
  onFileChangedpsb(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    this.docFIle1 = file;

    // Preview only for images
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.previewUrl1 = e.target?.result;
      };
      reader.readAsDataURL(file);
    } else {
      this.previewUrl1 = null;
    }

    // Reset input so same file can be reselected if needed
    event.target.value = '';
  }

  onFileChangedpsb2(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    this.docFIle2 = file;

    // Preview only for images
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.previewUrl2 = e.target?.result;
      };
      reader.readAsDataURL(file);
    } else {
      this.previewUrl2 = null;
    }

    // Reset input so same file can be reselected if needed
    event.target.value = '';
  }

 
  getCheckPointData1(){
    let checkType = 'Damage/Spillage Checklist PM';
    if(this.material_type == 'Raw Material')
      checkType = 'Damage/Spillage Checklist RM'
    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Receiving&form='+encodeURIComponent(checkType)).subscribe(response => {
      this.checkPointData = response;
    });
  }

  material_type = 'Raw Material';
  getPendingDamages() {
    this.service.get('store/raw.php?type=getPendingDamagesGeneralMaterial&material_type='+this.material_type).subscribe(response => {
      this.results = response;
    });
  }

  view(data) {
    this.damages =[];
    this.selectedReport = data;
    this.isView = true;
    this.total = +this.selectedReport['outer_damage'] ;
    
    for (let i = 0; i < +this.total; i++) {
      let temp = {};
      temp['container_no'] = i+1;
      temp["status"] = "Outer Damage";
      temp['remark'] = "";
      this.damages[this.damages.length] = temp;
    }
  }

  save(data) {

    if(!data.valid){
      alertify.error('All Field Required !!!!!');
      return;
    }

    const uploadData = new FormData();
   
    if (this.docFIle1) {
      uploadData.append('damaeImg1', this.docFIle1, this.docFIle1.name);
    } 

    if (this.docFIle2) {
      uploadData.append('damaeImg2', this.docFIle2, this.docFIle2.name);
    } 

    uploadData.append('po_no', this.selectedReport['po_no'] );
    uploadData.append('challan_no', this.selectedReport['challan_no'] );
    uploadData.append('total_damage', this.selectedReport['outer_damage'] );
    uploadData.append('containers', JSON.stringify(this.damages));
    uploadData.append('checkPointData', JSON.stringify(this.checkPointData));

    this.service.post('store/raw.php?type=saveDamageInspection&id=' + this.selectedReport['id'], uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Damage Container Inspection form send for QA Approval');
        this.isView = false;
        this.getPendingDamages();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }







  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

















}
