import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UvCalibrationComponent } from './uv-calibration.component';

describe('UvCalibrationComponent', () => {
  let component: UvCalibrationComponent;
  let fixture: ComponentFixture<UvCalibrationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UvCalibrationComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(UvCalibrationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
