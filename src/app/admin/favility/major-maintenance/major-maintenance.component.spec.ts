import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MajorMaintenanceComponent } from './major-maintenance.component';

describe('MajorMaintenanceComponent', () => {
  let component: MajorMaintenanceComponent;
  let fixture: ComponentFixture<MajorMaintenanceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MajorMaintenanceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MajorMaintenanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
