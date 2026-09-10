import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RcontrolComponent } from './rcontrol.component';

describe('RcontrolComponent', () => {
  let component: RcontrolComponent;
  let fixture: ComponentFixture<RcontrolComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RcontrolComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RcontrolComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
