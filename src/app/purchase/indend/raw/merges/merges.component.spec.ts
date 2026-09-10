import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MergesComponent } from './merges.component';

describe('MergesComponent', () => {
  let component: MergesComponent;
  let fixture: ComponentFixture<MergesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MergesComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MergesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
