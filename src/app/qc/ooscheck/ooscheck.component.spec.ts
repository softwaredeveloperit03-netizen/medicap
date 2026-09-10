import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OoscheckComponent } from './ooscheck.component';

describe('OoscheckComponent', () => {
  let component: OoscheckComponent;
  let fixture: ComponentFixture<OoscheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OoscheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OoscheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
