import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewrmstockComponent } from './newrmstock.component';

describe('NewrmstockComponent', () => {
  let component: NewrmstockComponent;
  let fixture: ComponentFixture<NewrmstockComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewrmstockComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewrmstockComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
