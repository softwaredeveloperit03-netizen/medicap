import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CanplanComponent } from './canplan.component';

describe('CanplanComponent', () => {
  let component: CanplanComponent;
  let fixture: ComponentFixture<CanplanComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CanplanComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CanplanComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
