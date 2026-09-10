import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RmpmindendComponent } from './rmpmindend.component';

describe('RmpmindendComponent', () => {
  let component: RmpmindendComponent;
  let fixture: ComponentFixture<RmpmindendComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RmpmindendComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RmpmindendComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
