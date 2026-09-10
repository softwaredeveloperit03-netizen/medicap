import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InspectionfhydComponent } from './inspectionfhyd.component';

describe('InspectionfhydComponent', () => {
  let component: InspectionfhydComponent;
  let fixture: ComponentFixture<InspectionfhydComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InspectionfhydComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InspectionfhydComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
