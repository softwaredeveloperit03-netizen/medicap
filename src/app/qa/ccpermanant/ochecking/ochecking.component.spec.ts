import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OcheckingComponent } from './ochecking.component';

describe('OcheckingComponent', () => {
  let component: OcheckingComponent;
  let fixture: ComponentFixture<OcheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OcheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OcheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
