import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Opostapproval1Component } from './opostapproval1.component';

describe('Opostapproval1Component', () => {
  let component: Opostapproval1Component;
  let fixture: ComponentFixture<Opostapproval1Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Opostapproval1Component ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Opostapproval1Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
