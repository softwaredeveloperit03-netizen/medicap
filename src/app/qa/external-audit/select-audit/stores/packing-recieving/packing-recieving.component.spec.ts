import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PackingRecievingComponent } from './packing-recieving.component';

describe('PackingRecievingComponent', () => {
  let component: PackingRecievingComponent;
  let fixture: ComponentFixture<PackingRecievingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PackingRecievingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PackingRecievingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
