import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {
  isView = false;
  results;
  remarks = '';
  stocks;
  material_type = '';
  selectedResult = [];
  plant_id:any;
  constructor(private service: DataAccessService) { 
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }


  ngOnInit() {
    this.getDispensingActivities();
    this.getMaterialOutDetails();
  }

  getDispensingActivities() {
    this.service.get('store/dispensing.php?type=getDispensingRequests&material_type=Raw Material').subscribe(response => {
      this.results = response;
    });
  }


  getMaterialOutDetails() {
    this.service.get('store/bincard.php?type=getMaterials&material_type=' + this.material_type).subscribe(response => {
      this.stocks = response;
    });
  }


  view(index) {
    this.selectedResult = this.results[index];
    console.log(this.selectedResult);
    this.isView = true;


const array = this.selectedResult['materials'];

    this.selectedResult['materials'].sort((a, b) => {
        if (a.stage < b.stage) return -1;
        if (a.stage > b.stage) return 1;
        return 0;
      });



      let colorIndex = 0;
      this.selectedResult['materials'].forEach(result => {
        if (!this.materialCodeColors[result.stage]) {
          this.materialCodeColors[result.stage] = this.colorList[colorIndex % this.colorList.length];
          colorIndex++;
        }
      });

 
  }




  materialCodeColors: { [key: string]: string } = {};
  colorList: string[] = [
    '#FFCDD2', '#C8E6C9', '#BBDEFB', '#FFECB3', '#D1C4E9', '#B2DFDB', '#FFF9C4', '#FFCCBC',
    '#F8BBD0', '#DCEDC8', '#B3E5FC', '#FFE0B2', '#E1BEE7', '#B2EBF2', '#FFF9C4', '#FFCCBC',
    '#EF9A9A', '#A5D6A7', '#90CAF9', '#FFE082', '#CE93D8', '#80DEEA', '#FFEB3B', '#FFAB91',
    '#E57373', '#81C784', '#64B5F6', '#FFD54F', '#BA68C8', '#4DD0E1', '#FFEB3B', '#FF7043',
    '#EF5350', '#66BB6A', '#42A5F5', '#FFCA28', '#AB47BC', '#26C6DA', '#FFEB3B', '#FF5722',
    '#F44336', '#4CAF50', '#2196F3', '#FFC107', '#9C27B0', '#00BCD4', '#FFEB3B', '#E64A19',
    '#E53935', '#43A047', '#1E88E5', '#FFB300', '#8E24AA', '#00ACC1', '#FDD835', '#D84315',
    '#D32F2F', '#388E3C', '#1976D2', '#FFA000', '#7B1FA2', '#0097A7', '#FBC02D', '#BF360C',
    '#C62828', '#2E7D32', '#1565C0', '#FF8F00', '#6A1B9A', '#00838F', '#F9A825', '#FF6F00',
    '#B71C1C', '#1B5E20', '#0D47A1', '#FF6F00', '#4A148C', '#006064', '#F57F17', '#E65100',
    '#D50000', '#00C853', '#2962FF', '#FFD600', '#AA00FF', '#00B8D4', '#C6FF00', '#DD2C00',
    '#FF1744', '#00E676', '#2979FF', '#FFC400', '#D500F9', '#00BFA5', '#AEEA00', '#FF3D00',
    '#F50057', '#69F0AE', '#448AFF', '#FFAB00', '#651FFF', '#00E5FF', '#76FF03', '#FF9100',
    '#FF4081', '#B2FF59', '#40C4FF', '#FFD740', '#7C4DFF', '#18FFFF', '#CCFF90', '#FFAB40'
  ];

  






  updatePhysicalStock(status, idx) {
    this.selectedResult['materials'][idx]['physical_stock'] = status;
  }
  save(remark) {
    if (remark != 'Accept') {
      alertify.error('Please enter remarks');
      return;
    }
    let materials = [];
    for (let x = 0; x < this.selectedResult['materials'].length; x++) {
      let mat = {
        "id": this.selectedResult['materials'][x]['id'],
        "physical_stock_status": this.selectedResult['materials'][x]['physical_stock']
      }
      if(mat['physical_stock_status']=='' || mat['physical_stock_status']==null || mat['physical_stock_status']==undefined){
        alertify.error('Please select physical stock status');
        return;
      }
      materials.push(mat);
    }
    let obj = {
      "id": this.selectedResult['id'],
      "status": remark,
      "remarks": this.remarks,
      "materials": materials
    }
    this.service.post('store/dispensing.php?type=saveRequestRM&id=' + this.selectedResult['id'], JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('data save successfuly');
        this.getDispensingActivities();
        this.isView = false;
      } else {
        alertify.error('some error occured!');
      }
    });
  }
}
