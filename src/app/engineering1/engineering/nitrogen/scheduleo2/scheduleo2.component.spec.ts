import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Scheduleo2Component } from './scheduleo2.component';

describe('Scheduleo2Component', () => {
  let component: Scheduleo2Component;
  let fixture: ComponentFixture<Scheduleo2Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Scheduleo2Component ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(Scheduleo2Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
