import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RlafComponent } from './rlaf.component';

describe('RlafComponent', () => {
  let component: RlafComponent;
  let fixture: ComponentFixture<RlafComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RlafComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RlafComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
