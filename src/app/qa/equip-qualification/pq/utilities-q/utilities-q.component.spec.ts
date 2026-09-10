import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UtilitiesQComponent } from './utilities-q.component';

describe('UtilitiesQComponent', () => {
  let component: UtilitiesQComponent;
  let fixture: ComponentFixture<UtilitiesQComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UtilitiesQComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(UtilitiesQComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
