import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PhyCondiConfigSysComponent } from './phy-condi-config-sys.component';

describe('PhyCondiConfigSysComponent', () => {
  let component: PhyCondiConfigSysComponent;
  let fixture: ComponentFixture<PhyCondiConfigSysComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PhyCondiConfigSysComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PhyCondiConfigSysComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
