import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExpmanComponent } from './expman.component';

describe('ExpmanComponent', () => {
  let component: ExpmanComponent;
  let fixture: ComponentFixture<ExpmanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ExpmanComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ExpmanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
