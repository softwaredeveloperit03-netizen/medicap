import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewteamComponent } from './newteam.component';

describe('NewteamComponent', () => {
  let component: NewteamComponent;
  let fixture: ComponentFixture<NewteamComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewteamComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewteamComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
